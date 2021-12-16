<?php


namespace App\Pockets;


use App\Constant\ApiBusinessCode;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Config;
use App\Models\SwitchModel;
use App\Models\TradeBuy;
use App\Models\User;
use App\Models\UserRelation;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class UserRelationPocket extends BasePocket
{
    /**
     * 创建用户关系数据
     *
     * @param  TradeBuy  $tradeBuy
     * @param  Wallet    $consumer
     * @param  Wallet    $beneficiary
     * @param  int       $type
     *
     * @return \App\Models\UserRelation
     */
    public function createUserRelation(TradeBuy $tradeBuy, Wallet $consumer, Wallet $beneficiary, int $type)
    {
        switch ($type) {
            case UserRelation::TYPE_PRIVATE_CHAT:
                $expiredAt = time() + (app()->environment('production') ? 604800 : 300);
                $member    = rep()->member->getQuery()->where('user_id', $consumer->user_id)->first();
                if ($member && ($memberExpiredAt = $member->getExpiredAt()) > time()) {
                    $expiredAt = $memberExpiredAt > $expiredAt ? $expiredAt : $memberExpiredAt;
                }
                break;
            case UserRelation::TYPE_LOOK_WECHAT:
            default:
                $expiredAt = time() + 86400;
        }

        $userRelationData = [
            'pay_id'         => $tradeBuy->id,
            'type'           => $type,
            'user_id'        => $consumer->user_id,
            'target_user_id' => $beneficiary->user_id,
            'expired_at'     => $expiredAt,
        ];

        return rep()->userRelation->getQuery()->create($userRelationData);
    }

    /**
     * 获取用户与某个用户当前次可购买的服务
     *
     * @param  User  $consumer
     * @param  User  $beneficiary
     * @param  int   $type
     *
     * @return ResultReturn
     */
    public function getUserSetUpRelationPrice(User $consumer, User $beneficiary, int $type)
    {
        $relationPrices = UserRelation::TYPE_PRICES;
        $isFreeUnlock   = $this->hasFreeUnlockOnToday($consumer);
        if ($isFreeUnlock['status']) {
            foreach ($relationPrices as $index => $relationPrice) {
                $relationPrices[$index] = 0;
            }
        } else {
            if ($type == UserRelation::TYPE_PRIVATE_CHAT) {
                unset($relationPrices[UserRelation::TYPE_LOOK_WECHAT]);
            }

            $flipRelationPrices = array_flip($relationPrices);
            $prices             = rep()->config->getQuery()->select('key', 'value')
                ->whereIn('key', array_keys($flipRelationPrices))->get();
            foreach ($prices as $price) {
                if ($type == UserRelation::TYPE_LOOK_WECHAT && $price->key == Config::KEY_UNLOCK_PRIVATE_CHAT_PRICE) {
                    $relationPrices[$flipRelationPrices[$price->key]] = 0;
                } else {
                    $relationPrices[$flipRelationPrices[$price->key]] = $price->value * 10;
                }
            }
        }

        if ($relationPrices) {
            $counts = rep()->userRelation->getQuery()->where('user_id', $consumer->id)
                ->select('type', DB::raw('count(*) as quantity'))
                ->where('target_user_id', $beneficiary->id)->whereIn('type', array_keys($relationPrices))
                ->where(function ($query) {
                    $query->where('expired_at', '>', time())->orWhere('expired_at', 0);
                })->groupBy('type')->get();
            foreach ($counts as $count) {
                if (isset($relationPrices[$count->type])) {
                    unset($relationPrices[$count->type]);
                }
            }
        }

        return $this->judgeUnlockPermissions($consumer, $beneficiary, $relationPrices, $type);
    }

    /**
     * 判断解锁条件 隐藏微信 和 距离等
     *
     * @param  User  $consumer
     * @param  User  $beneficiary
     * @param        $relationPrices
     * @param  int   $type
     *
     * @return ResultReturn
     */
    private function judgeUnlockPermissions(User $consumer, User $beneficiary, $relationPrices, int $type)
    {
        $authUserWallet = rep()->wallet->getQuery()->where('user_id', $consumer->id)->first();
        if (array_sum($relationPrices) > $authUserWallet->getRawOriginal('balance')) {
            return ResultReturn::failed('余额不足', ApiBusinessCode::LACK_OF_BALANCE);
        }

        if (isset($relationPrices[UserRelation::TYPE_LOOK_WECHAT])) {
            $switch = rep()->userSwitch->getQuery()->join('switch', 'switch.id', 'user_switch.switch_id')
                ->select('user_switch.id', 'user_switch.status')->where('user_switch.user_id', $beneficiary->id)
                ->where('switch.key', SwitchModel::KEY_LOCK_WECHAT)->first();
            if ($switch && $switch->status) {
                if ($type == UserRelation::TYPE_LOOK_WECHAT && $relationPrices[UserRelation::TYPE_LOOK_WECHAT]) {
                    return ResultReturn::failed('女生开启微信隐藏，请私聊沟通～', ApiBusinessCode::FORBID_COMMON);
                } else {
                    unset($relationPrices[UserRelation::TYPE_LOOK_WECHAT]);
                }
            }
        }
// elseif ($type == UserRelation::TYPE_LOOK_WECHAT) {
//            return ResultReturn::failed('', ApiBusinessCode::HAVE_UNLOCKED);
//        }

        // 100 公里限制
//        if (version_compare(user_agent()->clientVersion, '1.3.0', '>=')) {
//            if (isset($relationPrices[UserRelation::TYPE_LOOK_WECHAT])) {
//                $distance = pocket()->user->getDistanceUsers($consumer->id, $beneficiary->id);
//                if ($distance > 100 || $distance == -1) {
//                    if ($type == UserRelation::TYPE_LOOK_WECHAT) {
//                        return ResultReturn::failed('您和女生距离超过100km，为保证体验，请和女生商议过后再添加联系方式。',
//                            ApiBusinessCode::FORBID_COMMON);
//                    } else {
//                        unset($relationPrices[UserRelation::TYPE_LOOK_WECHAT]);
//                    }
//                }
//            }
//        }

        return ResultReturn::success($relationPrices);
    }

    /**
     * 判断用户是否可以免费解锁
     *
     * @param  User  $consumer
     *
     * @return array
     */
    public function hasFreeUnlockOnToday(User $consumer)
    {
        $member = rep()->member->getQuery()->where('user_id', $consumer->id)->first();
        $result = ['status' => false, 'unlocked_count' => 0,];

        if ($member && $member->start_at + $member->duration > time()) {
            $todayStartAt = strtotime(date('Y-m-d'));
            $startAt      = $member->getRawOriginal('start_at') >= $todayStartAt
                ? $member->getRawOriginal('start_at') : $todayStartAt;

            $subQuery = rep()->userRelation->getQuery()->where('user_id', $consumer->id)
                ->where('created_at', '>=', $startAt)->groupBy('target_user_id');

            $todayUnlockUserCount = rep()->member->getQuery()
                ->fromSub($subQuery, 'res')->withTrashed()->count();

            $result['status']         =
                $todayUnlockUserCount < UserRelation::VIP_FREE_UNLOCK_USER_COUNT;
            $result['unlocked_count'] = $todayUnlockUserCount;
        }

        return $result;
    }
}

<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Discount;
use App\Models\TradePay;
use App\Models\User;
use App\Models\UserAb;
use Illuminate\Support\Facades\DB;

class DiscountPocket extends BasePocket
{
    /**
     * 活跃赠送用户带过期时间的优惠卷
     *
     * @param  \App\Models\User|int  $user
     */
    public function activeGivingDiscountByInviteTest($user)
    {
        $userId = $user instanceof User ? $user->id : $user;

        $userDetail = rep()->user->getQuery()->select('user.id', 'user.uuid', 'ud.reg_version',
            'user.gender')->join('user_detail as ud', 'ud.user_id', 'user.id')
            ->where('user.id', $userId)->first();

        if (version_compare($userDetail->getRawOriginal('reg_version'), '2.2.0', '>=')
            && $userDetail->gender == User::GENDER_MAN) {
            $loginLogs = rep()->statRemainLoginLog->getQuery()->select('user_id',
                DB::raw('FROM_UNIXTIME(login_at,\'%Y-%m-%d\') as date'))
                ->where('user_id', $userId)->groupBy('date')
                ->orderBy('date', 'desc')->get();

            if ($loginLogs->count() >= 3 && $loginLogs->count() < 5) {
                $inviteTestRecord = rep()->userAb->getUserInviteTestRecord($userId);
                $inviteTestIsB    = $inviteTestRecord && $inviteTestRecord->inviteTestIsB();
                $giveDiscountDays = $inviteTestIsB ? 4 : 3;
                if ($loginLogs->count() == $giveDiscountDays) {
                    $resp = $this->checkAndCreateGivingDiscount($userId, $inviteTestIsB);
                    if ($resp->getStatus()) {
                        pocket()->common->commonQueueMoreByPocketJob(pocket()->netease,
                            'pageOpenOrAlertNotice', [$userDetail->uuid, 1]);
                    }
                }
            }
        }
    }

    /**
     * 检查并创建用户 活跃赠送的折扣
     *
     * @param  int   $userId
     * @param  bool  $inviteTestIsB
     *
     * @return mixed
     */
    protected function checkAndCreateGivingDiscount($userId, $inviteTestIsB)
    {
        return DB::transaction(function () use ($userId, $inviteTestIsB) {
            rep()->wallet->getQuery()->where('user_id', $userId)->lockForUpdate()->first();
            $giveDiscountCount = rep()->discount->getQuery()->where('user_id', $userId)
                ->where('related_type', Discount::RELATED_TYPE_GIVING)->count();
            $buyMemberCount = rep()->tradePay->getQuery()->where('user_id', $userId)
                ->where('related_type', TradePay::RELATED_TYPE_RECHARGE_VIP)
                ->where('done_at', '>', 0)->count();

            if (!$giveDiscountCount && !$buyMemberCount) {
                if (!$inviteTestIsB || rep()->discount->getOverlapDiscountSum($userId) < 0.3) {
                    $discountData = [
                        'related_type' => Discount::RELATED_TYPE_GIVING,
                        'user_id'      => $userId,
                        'platform'     => Discount::PLATFORM_COMMON,
                        'discount'     => Discount::ACTIVE_GIVING_DISCOUNT,
                        'expired_at'   => time() + (app()->environment('production')
                                ? 172800 : 1800)
                    ];

                    $discount = rep()->discount->getQuery()->create($discountData);
                    return ResultReturn::success($discount);
                }
            }

            return ResultReturn::failed('');
        });
    }
}

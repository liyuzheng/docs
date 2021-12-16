<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserAb;
use App\Models\TradePay;
use Illuminate\Support\Facades\DB;

/**
 * Ab的test
 *
 * Class ABTestJob
 * @package App\Jobs
 */
class ABTestJob extends Job
{
    protected $userId;
    protected $type;
    protected $userType;
    protected $args;
    protected $userDetail;
    protected $ab;
    protected $abDetail;

    /**
     * ABTestJob constructor.
     *
     * @param         $type
     * @param         $userId
     * @param  array  $args
     */
    public function __construct($type, $userId, array $args = [])
    {
        $this->type   = $type;
        $this->userId = $userId;
        $this->args   = $args;

    }

    public function prefix()
    {
        $this->ab         = mongodb('ab');
        $this->abDetail   = mongodb('ab_detail');
        $this->userDetail = rep()->userDetail->m()->where('user_id', $this->userId)->first();
        $user             = rep()->user->m()->where('id', $this->userId)->first();
        if ($user->gender === User::GENDER_MAN && version_compare($this->userDetail->reg_version, '2.2.0', '>=')) {
            $userAbInfo = rep()->userAb->m()
                ->where('user_id', $this->userId)
                ->whereIn('type', [UserAb::TYPE_MAN_INVITE_TEST_A, UserAb::TYPE_MAN_INVITE_TEST_B])
                ->first();
            if (!$userAbInfo) {
                return false;
            }
            $this->userType = $userAbInfo->type;
            $test           = $this->ab->where('type', $this->userType)->first();
            if (!$test) {
                $this->ab->insert([
                    'type'                          => $this->userType,
                    'user_count'                    => 0,//注册人数
                    'invited_count'                 => 0,//被邀请人数
                    'invite_count'                  => 0,//发起邀请人数
                    'user_recharge'                 => 0,//充值金额
                    'recharge_user_count'           => 0,//付费人数
                    'recharge_user_percent'         => 0,//付费率
                    'repurchase_user_count'         => 0,//复购人数
                    'repurchase_percent'            => 0,//复购率
                    'invited_recharge'              => 0,//被邀请人数充值金额
                    'invited_recharge_user_count'   => 0,//被邀请用户付费人数
                    'invited_recharge_user_percent' => 0,//被邀请用户付费率
                    'invite_recharge'               => 0,//发起邀请人充值金额
                    'invite_recharge_user_count'    => 0,//发起邀请用户付费人数
                    'invite_recharge_percent'       => 0,//发起邀请用户付费人数付费率
                ]);
            }

            return true;
        }

        return false;


    }

    public function handle()
    {
        return;
        if (!$this->prefix()) {
            return true;
        }
        switch ($this->type) {
            case 'register':
                /** 用户人数 */
                $this->userCount();
                /** 发起邀请人数 */
                $this->inviteCount();
                break;
            case 'recharge':
                /** 充值金额 */
                $this->userRecharge();
                /** 付费人数和付费率 */
                $this->rechargeUserCountAndPercent();
                /** 复购人数和复购率 */
                $this->repurchaseCountAndPercent();
                /** 发起邀请人数和充值金额 */
                $this->inviteCountAndRecharge();
                /** 支付细分数据 */
                $this->pay();
                break;
            default:
                break;

        }
    }

    /**
     * 用户人数
     */
    protected function userCount()
    {
        $this->ab->where('type', $this->userType)->increment('user_count');
    }

    /**
     * 统计发起邀请人数
     */
    protected function inviteCount()
    {
        if (!$this->userDetail || !$this->userDetail->inviter) return;
        //被邀请人数
        $this->ab->where('type', $this->userType)->increment('invited_count');
        $userAbInfo = rep()->userAb->m()
            ->where('user_id', $this->userDetail->inviter)
            ->whereIn('type', [UserAb::TYPE_MAN_INVITE_TEST_A, UserAb::TYPE_MAN_INVITE_TEST_B])
            ->first();
        if ($userAbInfo) {
            //发起邀请人数
            mongodb('invite_user')
                ->where('user_id', $this->userDetail->inviter)
                ->where('type', $userAbInfo->type)
                ->updateOrInsert([
                    'user_id' => $this->userDetail->inviter,
                    'type'    => $userAbInfo->type,
                ]);
            if ($userAbInfo->type == 201) {
                $count = mongodb('invite_user')
                    ->where('type', $userAbInfo->type)
                    ->where('user_id', '!=', 0)
                    ->count();
            } else {
                $userIds = rep()->userAb->m()
                    ->where('created_at', '>=', 1617033600)
                    ->where('type', $userAbInfo->type)
                    ->pluck('user_id')
                    ->toArray();
                $count   = rep()->userDetail->m()
                    ->whereIn('inviter', $userIds)
                    ->pluck('inviter')->count();
            }
            $this->ab->where('type', $userAbInfo->type)->update(['invite_count' => $count]);
        }

    }

    /**
     * 统计充值金额
     */
    protected function userRecharge()
    {
        $trade = rep()->tradePay->m()->where('id', $this->args['trade_pay_id'])->first();
        $trade && $this->ab->where('type', $this->userType)->increment('user_recharge', $trade->amount);
    }

    /**
     * 统计付费人数和付费率
     */
    protected function rechargeUserCountAndPercent()
    {
        $percent = 0;
        $mongoAB = $this->ab->where('type', $this->userType)->first();
        /** 付费人数和复购率 */
        $this->ab->where('type', $this->userType)->increment('recharge_user_count');
        if ($mongoAB && $mongoAB['user_count'] > 0) {
            $percent = round(($mongoAB['recharge_user_count'] + 1) / $mongoAB['user_count'], 2);
        }
        $this->ab->where('type', $this->userType)->update(['recharge_user_percent' => $percent]);
    }

    /**
     * 统计复购人数和复购率
     */
    protected function repurchaseCountAndPercent()
    {
        $mongoAB    = $this->ab->where('type', $this->userType)->first();
        $tradePayId = $this->args['trade_pay_id'] ?? 0;
        if (!$tradePayId) return;
        $repayCount = rep()->tradePay->m()->where('user_id', $this->userId)
            ->where('related_type', TradePay::RELATED_TYPE_RECHARGE_VIP)
            ->where('status', TradePay::STATUS_SUCCESS)
            ->where('id', '!=', $tradePayId)
            ->count();
        if ($repayCount === 1) {
            /** 复购人数【第二次购买就算复购，更多次数不计入】 */
            $this->ab->where('type', $this->userType)->increment('repurchase_user_count');
            /** 复购率 */
            $percent = 0;
            if ($mongoAB && isset($mongoAB['recharge_user_count']) && $mongoAB['recharge_user_count'] > 0) {
                $percent = round(($mongoAB['repurchase_user_count'] + 1) / $mongoAB['recharge_user_count'], 2);
            }
            $this->ab->where('type', $this->userType)->update(['repurchase_percent' => $percent]);
        }
    }

    /**
     * 统计发起邀请人数和充值金额
     */
    protected function inviteCountAndRecharge()
    {
        $trade    = rep()->tradePay->m()->where('id', $this->args['trade_pay_id'])->first();
        $amount   = $trade->amount ?? 0;
        $testInfo = $this->ab->where('type', $this->userType)->first();
        if (!$testInfo) return;
        /**  发起邀请人充值金额 */
        $inviter = mongodb('invite_user')->where('user_id', $this->userId)->first();
        $inviter && $this->ab->where('type', $this->userType)->increment('invite_recharge', $amount);
        if (!$this->userDetail || !$this->userDetail->inviter) return;
        /** 被邀请人数充值金额 */
        $this->ab->where('type', $this->userType)->increment('invited_recharge', $amount);
        /** 被邀请用户付费人数 */
        $this->ab->where('type', $this->userType)->increment('invited_recharge_user_count');
        /** 被邀请用户付费率 */
        if ($testInfo['invited_count'] > 0) {
            $rechargePercent = round(($testInfo['invited_recharge_user_count'] + 1) / $testInfo['invited_count'], 2);
            $this->ab->where('type', $this->userType)->update(['invited_recharge_user_percent' => $rechargePercent]);
        }
        /** 发起邀请用户付费人数  */
        mongodb('pay_user')
            ->where('user_id', $this->userId)
            ->where('type', $this->userType)
            ->updateOrInsert([
                'user_id' => $this->userId,
                'type'    => $this->userType,
            ]);
        $count = mongodb('pay_user')->where('type', $this->userType)->count();
        $this->ab->where('type', $this->userType)->update(['invite_recharge_user_count' => $count]);
        if ($testInfo['invite_count'] > 0) {
            $percent = round($count / $testInfo['invite_count'], 2);
            $this->ab->where('type', $this->userType)->update(['invite_recharge_percent' => $percent]);
        }
    }

    /**
     * 统计支付细分数据的四张表
     */
    public function pay()
    {
        $tradePayId = $this->args['trade_pay_id'] ?? 0;
        $tradePay   = rep()->tradePay->m()
            ->where('related_type', TradePay::RELATED_TYPE_RECHARGE_VIP)
            ->where('id', $tradePayId)
            ->first();
        if (!$tradePay) return;
        $this->active($tradePay);
        $this->discountPay($tradePay);
    }

    /**
     * 付费活跃天数
     *
     * @param   $tradePay
     */
    public function active($tradePay)
    {
        $days = rep()->statRemainLoginLog->m()
            ->select('user_id', DB::raw('FROM_UNIXTIME(login_at,\'%Y-%m-%d\') as date'))
            ->where('user_id', $this->userId)
            //            ->where('login_at', '<=', $tradePay->created_at)
            ->groupBy('date')
            ->get()
            ->count();
        if ($days <= 0) return;
        if ($days > 5) $days = -1;
        $card = rep()->card->m()->find($tradePay->related_id);
        $this->abDetail
            ->where('type', $this->userType)
            ->where('card_level', $card->level)
            ->where('day_type', $days)
            ->increment('count');
    }

    /**
     * 付费活跃天数
     *
     * @param   $tradePay
     */
    public function discountPay($tradePay)
    {
        if ($tradePay->discount >= 1) return;
        $discount = (int)((1 - $tradePay->discount) * 100);
        $card     = rep()->card->m()->where('id', $tradePay->related_id)->first();
        mongodb('ab_detail')
            ->where('type', (int)$this->userType)
            ->where('card_level', (int)$card->level)
            ->where('discount', $discount)
            ->increment('count');

        mongodb('ab_detail_error')->insert([
            'error' => json_encode([
                'type'       => (int)$this->userType,
                'card_level' => (int)$card->level,
                'discount'   => $discount,
            ])
        ]);
    }

    public function getLock($userType, $level, $discount)
    {
        $key    = sprintf(config('redis_keys.ab_test.key'), $userType, $level, $discount);
        $client = redis()->client();

        do {
            $timeout = 10;
            $value   = 1;
            $isLock  = $client->set($key, $value, $timeout);
            if ($isLock) {
                if ($client->get($key) == $value) {
                    $client->del($key);

                    return true;
                }
            } else {
                usleep(5000);
            }
        } while (!$isLock);

        return false;
    }


}

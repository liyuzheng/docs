<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Mail\InviteWarnMail;
use App\Models\InviteRecord;
use App\Models\TradePay;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Jobs\ABTestJob;
use App\Models\User;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use App\Jobs\GreetStaticJob;

class StatPocket extends BasePocket
{

    /**
     * 获得用户信息
     *
     * @param  int  $userId
     *
     * @return array
     */
    private function getUserInfo(int $userId)
    {
        $user       = rep()->user->getById($userId);
        $userDetail = rep()->userDetail->getByUserId($userId);

        return [$user, $userDetail];
    }

    /**
     * 邀请的用户注册了
     *
     * @param  int  $userId
     * @param  int  $timestamp
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function incrInviteUserReg(int $userId, int $timestamp)
    {
        [$user, $userDetail] = $this->getUserInfo($userId);

        $this->checkInviterInviteCountAndSendMail($userDetail->inviter, $user->id);
        pocket()->statDailyInvite->userRegisterStat($user, $userDetail,
            'simple_user', $timestamp);
    }


    /**
     * 邀请的用户成为了魅力女生
     *
     * @param  int  $userId
     * @param  int  $timestamp
     *
     * @return \App\Foundation\Modules\ResultReturn\ResultReturn
     */
    public function incrInviteCharmUserReg(int $userId, int $timestamp)
    {
        [$user, $userDetail] = $this->getUserInfo($userId);

        return pocket()->statDailyInvite->incrInviteCharmUserReg($user, $userDetail, $timestamp);
    }

    /**
     * 邀请的用户充值了
     *
     * @param  int  $userId
     * @param  int  $timestamp
     * @param  int  $amount
     *
     * @return \App\Foundation\Modules\ResultReturn\ResultReturn
     */
    public function incrInviteUserTopUp(int $userId, int $timestamp, int $amount)
    {
        [$user, $userDetail] = $this->getUserInfo($userId);

        return pocket()->statDailyInvite->incrInviteUserTopUp($user, $userDetail, $timestamp, $amount);
    }

    /**
     * 用户活跃统计
     *
     * @param  int  $userId
     * @param  int  $timestamp
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function statUserActive(int $userId, int $timestamp)
    {
        [$user, $userDetail] = $this->getUserInfo($userId);
        if(!is_null($user)){
            pocket()->statDailyActive->incrActiveCount($user, $timestamp);
        }
    }

    /**
     * 新用户注册和魅力女生认证统计
     *
     * @param  int     $userId
     * @param  int     $timestamp
     * @param  string  $type
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function statUserRegister(int $userId, int $timestamp, string $type = 'simple_user')
    {
        [$user, $userDetail] = $this->getUserInfo($userId);
        switch ($type) {
            case 'simple_user':
                pocket()->statDailyNewUser->newUserRegStat($userDetail);
                if ($userDetail->inviter) {
                    pocket()->statDailyInvite->userRegisterStat($user, $userDetail, $type, $timestamp);
                    $this->checkInviterInviteCountAndSendMail($userDetail->inviter, $user->id);
                }
                break;
            case 'charm_girl':
                pocket()->statDailyInvite->userRegisterStat($user, $userDetail, $type, $timestamp);
                break;
        }

        pocket()->statDailyActive->incrRegisterCount($type, $timestamp);
    }

    /**
     * 统计用户充值
     *
     * @param  int  $userId
     * @param  int  $tradeId
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function statUserTopUp(int $userId, int $tradeId)
    {
        [$user, $userDetail] = $this->getUserInfo($userId);
        $tradePay = rep()->tradePay->getQuery()->find($tradeId);

        pocket()->statDailyTrade->statRecharge($tradePay);
        pocket()->statDailyRecharge->rechargeStat($user, $userDetail, $tradePay);
        pocket()->statDailyNewUser->newUserTradeStat($user, $userDetail, $tradePay);
        if ($tradePay->getRawOriginal('related_type') == TradePay::RELATED_TYPE_RECHARGE_VIP) {
            pocket()->statDailyConsume->consumeStat($user, $userDetail, $tradePay);
            pocket()->statDailyMember->statBuyMember($user, $userDetail, $tradePay);
        }

        if ($userDetail->inviter) {
            pocket()->statDailyInvite->rechargeStat($user, $userDetail, $tradePay);
        }

        if (pocket()->statUser->whetherUpdateFirstTopUpSeconds($userId)) {
            pocket()->statUser->createOrUpdateFirstTopUpSecondsByFirstTradePay($userId);
        }
        $job = (new ABTestJob('recharge', $userId, ['trade_pay_id' => $tradeId]))
            ->onQueue('ab_test_statics');
        dispatch($job);

        $greetJob = (new GreetStaticJob('recharge', $userId))->onQueue('greet_pay_static');
        dispatch($greetJob);
    }

    /**
     * 统计用户代币消费
     *
     * @param  int  $userId
     * @param  int  $tradeId
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function statUserConsume(int $userId, int $tradeId)
    {
        [$user, $userDetail] = $this->getUserInfo($userId);
        $tradeBuy = rep()->tradeBuy->getQuery()->find($tradeId);
        pocket()->statDailyConsume->consumeStat($user, $userDetail, $tradeBuy);
    }

    /**
     * 统计用户提现
     *
     * @param  int  $tradeId
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function statUserWithdraw(int $tradeId)
    {
        $withdraw = rep()->tradeWithdraw->getQuery()->find($tradeId);
        pocket()->statDailyTrade->statWithdraw($withdraw);
    }

    /**
     * 取消魅力女生统计
     *
     * @param  int  $userId
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function statCharmGirlCancel(int $userId)
    {
        $userReview = rep()->userReview->getQuery()->where('user_id', $userId)
            ->orderBy('id', 'desc')->onlyTrashed()->first();
        if ($userReview) {
            [$user, $userDetail] = $this->getUserInfo($userId);
            $timestamp = $userReview->getRawOriginal('deleted_at');
            pocket()->statDailyActive->decrCharmGirlCount($timestamp);
            pocket()->statDailyInvite->charmGirlCancelStat($userDetail, $userReview, $timestamp);
        }
    }

    /**
     * 检查邀请人邀请数量并发送邮件
     *
     * @param $inviterId
     * @param $beInviterId
     */
    private function checkInviterInviteCountAndSendMail($inviterId, $beInviterId)
    {
        $todayStartAt = strtotime(date('Y-m-d'));
        $cacheKey     = config('redis_keys.cache.invite_warn_cache');
        if (Redis::exists($cacheKey)) {
            if (Redis::zscore($cacheKey, $inviterId) !== false) {
                return;
            }
        } else {
            $lock = new RedisLock(Redis::connection(), 'lock:' . $cacheKey, 3);
            $lock->block(3, function () use ($cacheKey, $todayStartAt) {
                if (!Redis::exists($cacheKey)) {
                    Redis::zadd($cacheKey, 0, 0);
                    Redis::expire($cacheKey, strtotime('+1 days', $todayStartAt) - time());
                }
            });
        }

        $inviterInviteCount = rep()->inviteRecord->getQuery()->where('user_id', $inviterId)
                ->where('created_at', '>=', $todayStartAt)->where('type', InviteRecord::TYPE_USER_REG)
                ->where('target_user_id', '!=', $beInviterId)->count() + 1;
        if ($inviterInviteCount >= 5) {
            $inviter = rep()->user->getQuery()->select('id', 'uuid', 'gender')
                ->where('id', $inviterId)->first();
            if ($inviter->getRawOriginal('gender') != User::GENDER_WOMEN) {
                $mail = new InviteWarnMail($inviter->uuid, $inviterInviteCount);
                Mail::to(config('custom.invite_warn_emails'))
                    ->queue($mail->onQueue('send_emails'));

                Redis::zadd($cacheKey, time(), $inviter->id);
            }
        }
    }
}

<?php


namespace App\Pockets;


use App\Models\StatDailyNewUser;
use App\Models\TradePay;
use App\Models\UserReview;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\StatDailyInvite;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class StatDailyInvitePocket extends BasePocket
{
    /**
     * 获取或创建当日的统计记录
     *
     * @param  string  $os
     * @param          $todayStartAt
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    private function getOrCreateRecord($os, $todayStartAt)
    {
        $lock = new RedisLock(Redis::connection(), 'lock:create_stat_daily_invite_record', 3);
        $lock->block(3, function () use ($os, $todayStartAt) {
            $recordCount = rep()->statDailyInvite->getQuery()->where('os', $os)->where('date',
                $todayStartAt)->count();
            if (!$recordCount) {
                $timestamp  = ['created_at' => time(), 'updated_at' => time()];
                $latest     = rep()->statDailyInvite->m()->orderByDesc('id')->limit(3)->get();
                $insertData = $fields = [];
                foreach ($latest as $item) {
                    $fields[$item->os] = [
                        'user_count'     => $item->user_count,
                        'recharge_total' => $item->getRawOriginal('recharge_total'),
                        'charm_count'    => $item->charm_count,
                        'man_count'      => $item->man_count,
                    ];
                }

                $os   = array_values(StatDailyInvite::USER_DETAIL_OS_MAPPING);
                $os[] = StatDailyInvite::OS_ALL;
                foreach ($os as $o) {
                    $insertData[] = array_merge($timestamp, [
                        'os'             => $o,
                        'date'           => $todayStartAt,
                        'user_count'     => isset($fields[$o]) ? $fields[$o]['user_count'] : 0,
                        'recharge_total' => isset($fields[$o]) ? $fields[$o]['recharge_total'] : 0,
                        'charm_count'    => isset($fields[$o]) ? $fields[$o]['charm_count'] : 0,
                        'man_count'      => isset($fields[$o]) ? $fields[$o]['man_count'] : 0,
                    ]);
                }

                rep()->statDailyInvite->getQuery()->insert($insertData);
            }
        });
    }

    /**
     * 被邀请用户充值时需要统计的数据
     *
     * @param  User        $user
     * @param  UserDetail  $userDetail
     * @param  TradePay    $tradePay
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function rechargeStat(User $user, UserDetail $userDetail, TradePay $tradePay)
    {
        if (substr($tradePay->trade_no, 0, 6) == '100000' && $tradePay->os == 100
            && app()->environment('production')) {
            return;
        }

        $todayStartAt = date('Y-m-d', $tradePay->getRawOriginal('created_at'));
        $recordOs     = StatDailyInvite::USER_DETAIL_OS_MAPPING[$userDetail->reg_os]
            ?? StatDailyInvite::OS_ANDROID;
        $this->getOrCreateRecord($recordOs, $todayStartAt);

        if ($userDetail->inviter) {
            $update = [
                'recharge_total'         => DB::raw('recharge_total + ' . $tradePay->getRawOriginal('amount')),
                'current_recharge_total' => DB::raw('current_recharge_total + ' . $tradePay->getRawOriginal('amount')),
            ];

            rep()->statDailyInvite->getQuery()->whereIn('os', [$recordOs, StatDailyInvite::OS_ALL])
                ->where('date', $todayStartAt)->update($update);
        }
    }

    /**
     * 被邀请用户注册或认证魅力女生时需要统计的数据
     *
     * @param  User        $user
     * @param  UserDetail  $userDetail
     * @param  string      $type
     * @param  int         $timestamp
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function userRegisterStat(User $user, UserDetail $userDetail, string $type, int $timestamp)
    {
        if ($this->checkAndCacheUserRegister($user->id, $type)) {
            $todayStartAt = date('Y-m-d', $timestamp);
            $recordOs     = StatDailyInvite::USER_DETAIL_OS_MAPPING[$userDetail->reg_os]
                ?? StatDailyInvite::OS_ANDROID;
            $this->getOrCreateRecord($recordOs, $todayStartAt);

            if ($userDetail->inviter) {
                if ($type == 'simple_user') {
                    $update = [
                        'user_count'         => DB::raw('user_count + 1'),
                        'current_user_count' => DB::raw('current_user_count + 1'),
                    ];

                    if ($user->gender == User::GENDER_MAN) {
                        $update['man_count']         = DB::raw('man_count + 1');
                        $update['current_man_count'] = DB::raw('current_man_count + 1');
                    }

                } else {
                    $update = [
                        'charm_count'         => DB::raw('charm_count + 1'),
                        'current_charm_count' => DB::raw('current_charm_count + 1'),
                    ];
                }

                rep()->statDailyInvite->getQuery()->whereIn('os', [$recordOs, StatDailyInvite::OS_ALL])
                    ->where('date', $todayStartAt)->update($update);
            }
        }
    }

    /**
     * 检查某个用户是否已经统计过
     *
     * @param  int     $userId
     * @param  string  $type
     *
     * @return bool
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    protected function checkAndCacheUserRegister($userId, $type)
    {
        $cacheKey = sprintf(config('redis_keys.cache.invite_stat_cache'), $type);
        $lock     = new RedisLock(Redis::connection(), 'lock:' . $cacheKey, 3);
        if (!Redis::exists($cacheKey)) {
            $lock->block(3, function () use ($cacheKey, $userId) {
                if (Redis::exists($cacheKey)) {
                    Redis::zadd($cacheKey, time(), $userId);
                    Redis::expire($cacheKey, strtotime(date('Y-m-d')) + 86399 - time());
                }
            });
        }

        $statAt = Redis::zscore($cacheKey, $userId);
        if (!$statAt) {
            Redis::zadd($cacheKey, time(), $userId);

            return true;
        }

        return false;
    }


    /**
     * 被邀请用户取消魅力女生认证时需要统计的数据
     *
     * @param  UserDetail  $userDetail
     * @param  UserReview  $userReview
     * @param  int         $timestamp
     */
    public function charmGirlCancelStat(UserDetail $userDetail, UserReview $userReview, int $timestamp)
    {
        $todayStartAt = date('Y-m-d', $timestamp);
        $recordOs     = StatDailyInvite::USER_DETAIL_OS_MAPPING[$userDetail->reg_os]
            ?? StatDailyInvite::OS_ANDROID;

        if ($userDetail->inviter) {
            $update = ['charm_count' => DB::raw('charm_count - 1')];
            $doneAt = $userReview->getRawOriginal('done_at');
            if ($doneAt >= strtotime($todayStartAt)) {
                $update['current_charm_count'] = DB::raw('current_charm_count - 1');
            }

            rep()->statDailyInvite->getQuery()->whereIn('os', [$recordOs, StatDailyInvite::OS_ALL])
                ->where('date', $todayStartAt)->update($update);
        }
    }
}

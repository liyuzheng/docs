<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\StatDailyConsume;
use App\Models\StatDailyNewUser;
use App\Models\StatDailyRecharge;
use App\Models\TradePay;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class StatDailyNewUserPocket extends BasePocket
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
        $lock = new RedisLock(Redis::connection(), 'lock:create_stat_daily_new_user_record', 3);
        $lock->block(3, function () use ($os, $todayStartAt) {
            $recordCount = rep()->statDailyNewUser->getQuery()->where('os', $os)->where('date',
                $todayStartAt)->count();
            if (!$recordCount) {
                $iosRecord = $androidRecord = $allRecord = [
                    'os'         => StatDailyNewUser::OS_ALL,
                    'date'       => $todayStartAt,
                    'created_at' => time(),
                    'updated_at' => time()
                ];

                $iosRecord['os']     = StatDailyNewUser::OS_IOS;
                $androidRecord['os'] = StatDailyNewUser::OS_ANDROID;

                rep()->statDailyNewUser->getQuery()->insert([$allRecord, $iosRecord, $androidRecord]);
            }
        });
    }

    /**
     * 用户充值统计
     *
     * @param  User        $user
     * @param  UserDetail  $userDetail
     * @param  TradePay    $tradePay
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     * TODO: 未统计代币购买会员
     */
    public function newUserTradeStat(User $user, UserDetail $userDetail, TradePay $tradePay)
    {
        if ($tradePay->getRawOriginal('related_type') == TradePay::RELATED_TYPE_RECHARGE
            && (substr($tradePay->trade_no, 0, 6) == '100000' && $tradePay->os == 100
                && app()->environment('production'))) {
            return;
        }

        $todayStartAt = date('Y-m-d', $tradePay->getRawOriginal('created_at'));
        $recordOs     = StatDailyNewUser::USER_DETAIL_OS_MAPPING[$userDetail->reg_os]
            ?? StatDailyNewUser::OS_ANDROID;
        $this->getOrCreateRecord($recordOs, $todayStartAt);

        $userIsNew        = $user->getRawOriginal('created_at') >= strtotime($todayStartAt);
        $repeatPayBuilder = rep()->tradePay->getQuery()->where('user_id', $user->id)
            ->where('id', '!=', $tradePay->id)->where('created_at', '>=', strtotime($todayStartAt))
            ->where('done_at', '!=', 0);
        $update           = [];

        if (!(clone $repeatPayBuilder)->count() && $userIsNew) {
            $update['new_recharge_count'] = DB::raw('new_recharge_count + 1');
        }

        if (!$repeatPayBuilder->where('related_type',
                TradePay::RELATED_TYPE_RECHARGE_VIP)->count() && $tradePay->getRawOriginal('related_type')
            == TradePay::RELATED_TYPE_RECHARGE_VIP) {
            $memberCountField          = $userIsNew ? 'new_member_count' : 'old_member_count';
            $update[$memberCountField] = DB::raw($memberCountField . ' + 1');
        }

        if ($update) {
            rep()->statDailyNewUser->getQuery()->whereIn('os', [$recordOs, StatDailyNewUser::OS_ALL])
                ->where('date', $todayStartAt)->update($update);
        }
    }

    /**
     * 用户注册统计
     *
     * @param  UserDetail  $userDetail
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function newUserRegStat(UserDetail $userDetail)
    {
        $todayStartAt = date('Y-m-d', $userDetail->getRawOriginal('created_at'));
        $recordOs     = StatDailyNewUser::USER_DETAIL_OS_MAPPING[$userDetail->reg_os]
            ?? StatDailyNewUser::OS_ANDROID;
        $this->getOrCreateRecord($recordOs, $todayStartAt);
        rep()->statDailyNewUser->getQuery()->whereIn('os', [$recordOs, StatDailyNewUser::OS_ALL])
            ->where('date', $todayStartAt)->increment('new_user_count');
    }
}

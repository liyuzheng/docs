<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\StatDailyRecharge;
use App\Models\TradePay;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class StatDailyRechargePocket extends BasePocket
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
        $lock = new RedisLock(Redis::connection(), 'lock:create_stat_daily_recharge_record', 3);
        $lock->block(3, function () use ($os, $todayStartAt) {
            $recordCount = rep()->statDailyRecharge->getQuery()->where('os', $os)->where('date',
                $todayStartAt)->count();

            if (!$recordCount) {
                $iosRecord = $androidRecord = $allRecord = [
                    'os'         => StatDailyRecharge::OS_ALL,
                    'date'       => $todayStartAt,
                    'created_at' => time(),
                    'updated_at' => time()
                ];

                $iosRecord['os']     = StatDailyRecharge::OS_IOS;
                $androidRecord['os'] = StatDailyRecharge::OS_ANDROID;

                rep()->statDailyRecharge->getQuery()
                    ->insert([$iosRecord, $allRecord, $androidRecord]);
            }
        });
    }

    /**
     * 充值统计
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
        $recordOs     = StatDailyRecharge::USER_DETAIL_OS_MAPPING[$userDetail->reg_os]
            ?? StatDailyRecharge::OS_ANDROID;
        $this->getOrCreateRecord($recordOs, $todayStartAt);

        $newOrOldFiled = $user->getRawOriginal('created_at') >= strtotime($todayStartAt)
            ? 'new_user_total' : 'old_user_total';
        $genderFiled   = $user->gender == User::GENDER_WOMEN ? 'woman_total' : 'man_total';

        $updateFields = [
            'top_up_total' => DB::raw('top_up_total + ' . $tradePay->amount),
            $newOrOldFiled => DB::raw($newOrOldFiled . ' + ' . $tradePay->amount),
            $genderFiled   => DB::raw($genderFiled . ' + ' . $tradePay->amount),
        ];

        rep()->statDailyRecharge->getQuery()->whereIn('os', [StatDailyRecharge::OS_ALL, $recordOs])
            ->where('date', $todayStartAt)->update($updateFields);
    }
}

<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\StatDailyMember;
use App\Models\TradePay;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class StatDailyMemberPocket extends BasePocket
{
    /**
     * 获取或创建当日的统计记录
     *
     * @param  string  $os
     * @param  string  $level
     * @param  string  $todayStartAt
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function getOrCreateRecord($todayStartAt, $os, $level)
    {
        $lock = new RedisLock(Redis::connection(), 'lock:create_stat_daily_trade_record', 3);
        $lock->block(3, function () use ($todayStartAt, $os, $level) {
            $recordCount = rep()->statDailyMember->getQuery()->where('os', $os)->where('level', $level)
                ->where('date', $todayStartAt)->count();

            if (!$recordCount) {
                $timestamps  = ['created_at' => time(), 'updated_at' => time()];
                $needRecords = $insertData = $yesterdayItems = [];
                $clients     = array_values(StatDailyMember::USER_DETAIL_OS_MAPPING);
                $levels      = array_keys(StatDailyMember::STAT_CARD_LEVELS);
                foreach ($clients as $item) {
                    $needRecords[$item] = $levels;
                }

                $todayRecords = rep()->statDailyMember->getQuery()->where('date', $todayStartAt)->get();
                foreach ($todayRecords as $todayRecord) {
                    if (isset($needRecords[$todayRecord->os][$todayRecord->level])) {
                        unset($needRecords[$todayRecord->os][$todayRecord->level]);
                    }
                }

                $yesterdayStats = rep()->statDailyMember->getQuery()->orderByDesc('id')
                    ->limit(count($clients) * count($levels))->get();
                foreach ($yesterdayStats as $yesterdayStat) {
                    $index = $yesterdayStat->os . $yesterdayStat->getRawOriginal('level');
                    if (!isset($yesterdayItems[$index])) {
                        $yesterdayItems[$index] = [
                            'member_count'  => $yesterdayStat->member_count,
                            'renewal_count' => $yesterdayStat->renewal_count
                        ];
                    }
                }

                foreach ($needRecords as $needRecordOs => $needRecordLevels) {
                    foreach ($needRecordLevels as $needRecordLevel) {
                        $insertData[] = array_merge([
                            'date'          => $todayStartAt,
                            'os'            => $needRecordOs,
                            'level'         => $needRecordLevel,
                            'member_count'  => isset($yesterdayItems[$needRecordOs . $needRecordLevel])
                                ? $yesterdayItems[$needRecordOs . $needRecordLevel]['member_count'] : 0,
                            'renewal_count' => isset($yesterdayItems[$needRecordOs . $needRecordLevel])
                                ? $yesterdayItems[$needRecordOs . $needRecordLevel]['renewal_count'] : 0,
                        ], $timestamps);
                    }
                }

                rep()->statDailyMember->getQuery()->insert($insertData);
            }
        });
    }

    /**
     * 会员购买统计
     *
     * @param  User        $user
     * @param  UserDetail  $userDetail
     * @param  TradePay    $tradePay
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     * TODO: 未统计代币购买会员
     */
    public function statBuyMember(User $user, UserDetail $userDetail, TradePay $tradePay)
    {
        $todayStartAt = date('Y-m-d', $tradePay->getRawOriginal('created_at'));
        $count        = rep()->tradePay->getQuery()->where('user_id', $user->id)
            ->where('related_type', TradePay::RELATED_TYPE_RECHARGE_VIP)->where('id', '!=', $tradePay->id)
            ->where('done_at', '!=', 0)->count();
        $card         = rep()->card->getQuery()->find($tradePay->related_id);
        $recordOs     = StatDailyMember::USER_DETAIL_OS_MAPPING[$userDetail->reg_os]
            ?? StatDailyMember::OS_ANDROID;
        $this->getOrCreateRecord($todayStartAt, $recordOs, $card->level);

        $todayRenewalCount = rep()->tradePay->getQuery()->where('user_id', $user->id)
            ->where('related_type', TradePay::RELATED_TYPE_RECHARGE_VIP)->where('id', '!=', $tradePay->id)
            ->where('done_at', '!=', 0)->where('created_at', '>=', strtotime($todayStartAt))->count();
        $updates           = [];
        $isRenewal         = app()->environment('production') ? $count && !$todayRenewalCount
            : $count && $todayRenewalCount < 2;


        if ($isRenewal) {
            $lastMemberRecord = rep()->memberRecord->getQuery()->where('user_id', $user->id)
                ->orderBy('id', 'desc')->skip(1)->first();

            if ($lastMemberRecord) {
                $lastRecordCard = rep()->tradePay->getQuery()->select('card.*')
                    ->join('card', 'card.id', 'trade_pay.related_id')
                    ->where('trade_pay.id', $lastMemberRecord->pay_id)
                    ->first();

                if (date('Y-m-d', $lastMemberRecord->getRawOriginal('expired_at')) == $todayStartAt) {
                    $updates[0]['current_renewal_count'] = DB::raw('current_renewal_count + 1');
                    if ($card->level == $lastRecordCard->level) {
                        $updates[$card->level]['current_renewal_count'] = $updates[0]['current_renewal_count'];
                    }
                }

                if ($count == 1) {
                    $updates[0]['renewal_count'] = DB::raw('renewal_count + 1');
                    if ($card->level == $lastRecordCard->level) {
                        $updates[$card->level]['renewal_count'] = $updates[0]['renewal_count'];
                    }
                }
            }
        } elseif (!$count) {
            $updates[0]            = ['member_count' => DB::raw('member_count + 1')];
            $updates[$card->level] = $updates[0];
        }

        foreach ($updates as $level => $update) {
            rep()->statDailyMember->getQuery()->where('os', $recordOs)
                ->where('level', $level)->where('date', $todayStartAt)
                ->update($update);
        }
    }
}

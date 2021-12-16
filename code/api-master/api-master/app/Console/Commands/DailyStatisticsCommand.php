<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trade;
use Illuminate\Support\Facades\DB;
use App\Models\Member;
use App\Models\Card;

class DailyStatisticsCommand extends Command
{
    protected $signature   = 'xiaoquan:daily_statistics';
    protected $description = '统计用户日常数据';

    public function handle()
    {
        $startTime    = strtotime(date('Y-m-d', time())) - 86400;
        $endTime      = $startTime + 86399;
        $activeCount  = rep()->statRemainLoginLog->m()
            ->whereBetween('login_at', [$startTime, $endTime])
            ->count([DB::raw('distinct(user_id)')]);
        $newUserCount = rep()->user->m()
            ->whereBetween('created_at', [$startTime, $endTime])
            ->count();

        $groupNewUserCount = rep()->userDetail->m()
            ->select(['os', DB::raw('count(*) as count')])
            ->whereBetween('created_at', [$startTime, $endTime])
            ->groupBy('os')
            ->get();
        $androidNewCount   = 0;
        $iosNewCount       = 0;
        foreach ($groupNewUserCount as $item) {
            if ($item->os == 'android') {
                $androidNewCount = $item->count;
            }
            if ($item->os == 'ios') {
                $iosNewCount = $item->count;
            }
        }

        $newUserTradeCount = rep()->tradePay->m()
            ->select(DB::raw('sum(trade_pay.amount) as amount'))
            ->join('user', 'user.id', '=', 'trade_pay.user_id')
            ->whereBetween('user.created_at', [$startTime, $endTime])
            ->whereBetween('trade_pay.done_at', [$startTime, $endTime])
            ->where('trade_pay.trade_no', 'not like', '10000%')
            ->first();

        $groupNewUserTradeCount   = rep()->tradePay->m()
            ->select([DB::raw('sum(trade_pay.amount) as amount'), 'user_detail.os'])
            ->join('user_detail', 'user_detail.user_id', '=', 'trade_pay.user_id')
            ->whereBetween('user_detail.created_at', [$startTime, $endTime])
            ->whereBetween('trade_pay.done_at', [$startTime, $endTime])
            ->where('trade_pay.trade_no', 'not like', '10000%')
            ->groupBy('user_detail.os')
            ->get();
        $androidNewUserTradeCount = 0;
        $iosNewUserTradeCount     = 0;
        foreach ($groupNewUserTradeCount as $item) {
            if ($item->os == 'android') {
                $androidNewUserTradeCount = $item->amount;
            }
            if ($item->os == 'ios') {
                $iosNewUserTradeCount = $item->amount;
            }
        }

        $newUserTradeNumber = rep()->tradePay->m()
            ->join('user', 'user.id', '=', 'trade_pay.user_id')
            ->whereBetween('user.created_at', [$startTime, $endTime])
            ->where('trade_pay.amount', '!=', 0)
            ->where('trade_pay.trade_no', 'not like', '10000%')
            ->where('trade_pay.done_at', '!=', '0')
            ->count([DB::raw('distinct(trade_pay.user_id)')]);

        $groupNewUserTradeNumber = rep()->tradePay->m()
            ->select([DB::raw('count(distinct(trade_pay.user_id)) as trade_number'), 'user_detail.os'])
            ->join('user_detail', 'user_detail.user_id', '=', 'trade_pay.user_id')
            ->whereBetween('user_detail.created_at', [$startTime, $endTime])
            ->where('trade_pay.amount', '!=', 0)
            ->where('trade_pay.trade_no', 'not like', '10000%')
            ->where('trade_pay.done_at', '!=', '0')
            ->groupBy('user_detail.os')
            ->get();
        $androidNewTradeNumber   = 0;
        $iosNewTradeNumber       = 0;
        foreach ($groupNewUserTradeNumber as $item) {
            if ($item->os == 'android') {
                $androidNewTradeNumber = $item->trade_number;
            }
            if ($item->os == 'ios') {
                $iosNewTradeNumber = $item->trade_number;
            }
        }

        $tradeCount = rep()->tradePay->m()
            ->select(DB::raw('sum(amount) as amount'))
            ->whereBetween('done_at', [$startTime, $endTime])
            ->where('trade_no', 'not like', '10000%')
            ->first();

        $groupTradeCount   = rep()->tradePay->m()
            ->select([DB::raw('sum(trade_pay.amount) as trade_count'), 'user_detail.os'])
            ->join('user_detail', 'trade_pay.user_id', '=', 'user_detail.user_id')
            ->whereBetween('trade_pay.done_at', [$startTime, $endTime])
            ->where('trade_pay.trade_no', 'not like', '10000%')
            ->groupBy('user_detail.os')
            ->get();
        $androidTradeCount = 0;
        $iosTradeCount     = 0;
        foreach ($groupTradeCount as $item) {
            if ($item->os == 'android') {
                $androidTradeCount = $item->trade_count;
            }
            if ($item->os == 'ios') {
                $iosTradeCount = $item->trade_count;
            }
        }

        $freeCard       = rep()->card->m()->where('level', Card::LEVEL_YEAR)
            ->where('continuous', 0)->first();
        $newMemberCount = rep()->member->m()
            ->whereBetween('created_at', [$startTime, $endTime])
            ->where('card_id', '!=', $freeCard->id)
            ->count();

        $roles            = pocket()->role->getUserRoleArr(['charm_girl']);
        $activeCharmCount = rep()->statRemainLoginLog->m()
            ->join('user', 'user.id', '=', 'stat_remain_login_log.user_id')
            ->whereBetween('stat_remain_login_log.login_at', [$startTime, $endTime])
            ->whereIn('user.role', $roles)
            ->count([DB::raw('distinct(user_id)')]);

        $groupActiveCharmCount = rep()->statRemainLoginLog->m()
            ->select(['user_detail.os', DB::raw('count(*) as count')])
            ->join('user', 'user.id', '=', 'stat_remain_login_log.user_id')
            ->whereBetween('stat_remain_login_log.login_at', [$startTime, $endTime])
            ->join('user_detail', 'user.id', '=', 'user_detail.user_id')
            ->whereIn('user.role', $roles)
            ->groupBy('user_detail.os')
            ->get();
        $androidActiveCharm    = 0;
        $iosActiveCharm        = 0;
        foreach ($groupActiveCharmCount as $item) {
            if ($item->os == 'android') {
                $androidActiveCharm = $item->count;
            }
            if ($item->os == 'ios') {
                $iosActiveCharm = $item->count;
            }
        }
        $createData = [
            'date'                   => date('Y-m-d H:i:s', $startTime),
            'active'                 => $activeCount,
            'charm_active'           => $activeCharmCount,
            'android_charm_active'   => $androidActiveCharm,
            'ios_charm_active'       => $iosActiveCharm,
            'new_user'               => $newUserCount,
            'android_new_user'       => $androidNewCount,
            'ios_new_user'           => $iosNewCount,
            'trade'                  => $tradeCount->amount ? $tradeCount->amount : 0,
            'android_trade'          => $androidTradeCount,
            'ios_trade'              => $iosTradeCount,
            'new_user_trade'         => $newUserTradeCount->amount ? $newUserTradeCount->amount : 0,
            'android_new_user_trade' => $androidNewUserTradeCount,
            'ios_new_user_trade'     => $iosNewUserTradeCount,
            'new_member'             => $newMemberCount,
            'trade_rate'             => $newUserTradeNumber / $newUserCount,
            'android_trade_rate'     => $androidNewTradeNumber / $androidNewCount,
            'ios_trade_rate'         => $iosNewTradeNumber / $iosNewCount,
        ];
        rep()->dailyRecord->m()->create($createData);
        dd('添加成功');
    }
}

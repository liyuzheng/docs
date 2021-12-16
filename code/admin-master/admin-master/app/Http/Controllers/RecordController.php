<?php


namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Card;
use Illuminate\Http\Request;

class RecordController extends BaseController
{
    /**
     * 日常数据
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dailyRecord(Request $request)
    {
        $page      = $request->get('page', 1);
        $limit     = $request->get('limit', 10);
        $startTime = $request->get('start_time', '1970-01-01');
        $endTime   = $request->get('end_time', date('Y-m-d H:i:s', time()));
        if (!$page) {
            $page = 1;
        }
        if (!$limit) {
            $limit = 10;
        }
        if (!$startTime) {
            $startTime = '1970-01-01';
        }
        if (!$endTime) {
            $endTime = date('Y-m-d H:i:s', time());
        }
        $result = [];
        if (strtotime($endTime) > strtotime(date('Y-m-d', time()))) {
            $endTime = date('Y-m-d H:i:s', time());
            $offset  = (($page - 1) * $limit) - 1;
            if ($page == 1) {
                $todayStart   = strtotime(date('Y-m-d', time()));
                $todayEnd     = $todayStart + 86399;
                $limit        -= 1;
                $activeCount  = rep()->statRemainLoginLog->m()
                    ->whereBetween('login_at', [$todayStart, $todayEnd])
                    ->count([DB::raw('distinct(user_id)')]);
                $newUserCount = rep()->user->m()->whereBetween('created_at', [$todayStart, $todayEnd])->count();

                $groupNewUserCount = rep()->userDetail->m()
                    ->select(['os', DB::raw('count(*) as count')])
                    ->whereBetween('created_at', [$todayStart, $todayEnd])
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
                    ->whereBetween('user.created_at', [$todayStart, $todayEnd])
                    ->where('trade_pay.done_at', '!=', 0)
                    ->whereBetween('trade_pay.created_at', [$todayStart, $todayEnd])
                    ->where('trade_pay.trade_no', 'not like', '10000%')
                    ->first();

                $groupNewUserTradeCount   = rep()->tradePay->m()
                    ->select([DB::raw('sum(trade_pay.amount) as amount'), 'user_detail.os'])
                    ->join('user_detail', 'user_detail.user_id', '=', 'trade_pay.user_id')
                    ->whereBetween('user_detail.created_at', [$todayStart, $todayEnd])
                    ->where('trade_pay.done_at', '!=', 0)
                    ->whereBetween('trade_pay.created_at', [$todayStart, $todayEnd])
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
                    ->whereBetween('user.created_at', [$todayStart, $todayEnd])
                    ->where('trade_pay.amount', '!=', 0)
                    ->where('trade_pay.trade_no', 'not like', '10000%')
                    ->where('trade_pay.done_at', '!=', 0)
                    ->whereBetween('trade_pay.created_at', [$todayStart, $todayEnd])
                    ->count([DB::raw('distinct(trade_pay.user_id)')]);

                $groupNewUserTradeNumber = rep()->tradePay->m()
                    ->select([DB::raw('count(distinct(trade_pay.user_id)) as trade_number'), 'user_detail.os'])
                    ->join('user_detail', 'user_detail.user_id', '=', 'trade_pay.user_id')
                    ->whereBetween('user_detail.created_at', [$todayStart, $todayEnd])
                    ->where('trade_pay.amount', '!=', 0)
                    ->where('trade_pay.trade_no', 'not like', '10000%')
                    ->where('trade_pay.done_at', '!=', 0)
                    ->whereBetween('trade_pay.created_at', [$todayStart, $todayEnd])
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
                    ->whereBetween('done_at', [$todayStart, $todayEnd])
                    ->where('trade_no', 'not like', '10000%')
                    ->first();

                $groupTradeCount   = rep()->tradePay->m()
                    ->select([DB::raw('sum(trade_pay.amount) as trade_count'), 'user_detail.os'])
                    ->join('user_detail', 'trade_pay.user_id', '=', 'user_detail.user_id')
                    ->whereBetween('trade_pay.done_at', [$todayStart, $todayEnd])
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

                $freeCardIds    = rep()->card->m()->whereIn('level', [Card::LEVEL_YEAR, Card::LEVEL_FREE_VIP])
                    ->where('continuous', 0)->pluck('id')->toArray();
                $newMemberCount = rep()->member->m()
                    ->whereBetween('created_at', [$todayStart, $todayEnd])
                    ->whereNotIn('card_id', $freeCardIds)
                    ->count();

                $roles            = pocket()->role->getUserRoleArr(['charm_girl']);

                $activeCharmCount = rep()->statRemainLoginLog->m()
                    ->join('user','user.id','=','stat_remain_login_log.user_id')
                    ->whereBetween('stat_remain_login_log.login_at', [$todayStart, $todayEnd])
                    ->whereIn('user.role',$roles)
                    ->count([DB::raw('distinct(user_id)')]);

                $groupActiveCharmCount = rep()->statRemainLoginLog->m()
                    ->select(['user_detail.os', DB::raw('count(*) as count')])
                    ->join('user','user.id','=','stat_remain_login_log.user_id')
                    ->whereBetween('stat_remain_login_log.login_at', [$todayStart, $todayEnd])
                    ->join('user_detail', 'user.id', '=', 'user_detail.user_id')
                    ->whereBetween('user.active_at', [$todayStart, $todayEnd])
                    ->whereIn('user.role',$roles)
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
                $firstData          = [
                    'date'                   => date('Y-m-d H:i:s', time()),
                    'active'                 => $activeCount,
                    'charm_active'           => $activeCharmCount,
                    'android_charm_active'   => $androidActiveCharm,
                    'ios_charm_active'       => $iosActiveCharm,
                    'new_user'               => $newUserCount,
                    'android_new_user'       => $androidNewCount,
                    'ios_new_user'           => $iosNewCount,
                    'trade'                  => $tradeCount->amount != null ? $tradeCount->amount / 100 : 0,
                    'android_trade'          => $androidTradeCount / 100,
                    'ios_trade'              => $iosTradeCount / 100,
                    'new_user_trade'         => $newUserTradeCount->amount != null ? $newUserTradeCount->amount / 100 : 0,
                    'android_new_user_trade' => $androidNewUserTradeCount / 100,
                    'ios_new_user_trade'     => $iosNewUserTradeCount / 100,
                    'new_member'             => $newMemberCount,
                    'trade_rate'             => $newUserCount != 0 ? (round($newUserTradeNumber / $newUserCount,
                            5)) * 100 . '%' : 0,
                    'android_trade_rate'     => $androidNewCount != 0 ? (round($androidNewTradeNumber / $androidNewCount,
                            5)) * 100 . '%' : 0,
                    'ios_trade_rate'         => $iosNewCount != 0 ? (round($iosNewTradeNumber / $iosNewCount,
                            5)) * 100 . '%' : 0,
                ];
                $result['record'][] = $firstData;
            }
            $list  = rep()->dailyRecord->m()
                ->whereBetween('date', [$startTime, $endTime])
                ->limit($limit)
                ->offset($offset)
                ->orderByDesc('id')
                ->get();
            $count = rep()->dailyRecord->m()
                ->whereBetween('date', [$startTime, $endTime])
                ->count();
            $count += 1;
        } else {
            $offset = ($page - 1) * $limit;
            $list   = rep()->dailyRecord->m()
                ->whereBetween('date', [$startTime, $endTime])
                ->limit($limit)
                ->offset($offset)
                ->orderByDesc('id')
                ->get();
            $count  = rep()->dailyRecord->m()
                ->whereBetween('date', [$startTime, $endTime])
                ->count();
        }

        foreach ($list as $item) {
            $data               = [
                'date'                   => $item->date,
                'active'                 => $item->active,
                'charm_active'           => $item->charm_active,
                'android_charm_active'   => $item->android_charm_active,
                'ios_charm_active'       => $item->ios_charm_active,
                'new_user'               => $item->new_user,
                'android_new_user'       => $item->android_new_user,
                'ios_new_user'           => $item->ios_new_user,
                'trade'                  => $item->trade / 100,
                'android_trade'          => $item->android_trade / 100,
                'ios_trade'              => $item->ios_trade / 100,
                'new_user_trade'         => $item->new_user_trade / 100,
                'android_new_user_trade' => $item->android_new_user_trade / 100,
                'ios_new_user_trade'     => $item->ios_new_user_trade / 100,
                'new_member'             => $item->new_member,
                'trade_rate'             => ($item->trade_rate * 100) . '%',
                'android_trade_rate'     => ($item->android_trade_rate * 100) . '%',
                'ios_trade_rate'         => ($item->ios_trade_rate * 100) . '%',
            ];
            $result['record'][] = $data;
        }
        $result['count'] = $count;

        return api_rr()->getOK($result);
    }
}

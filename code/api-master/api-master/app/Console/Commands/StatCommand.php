<?php

namespace App\Console\Commands;


use App\Models\Role;
use App\Models\Trade;
use App\Models\TradePay;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StatCommand extends Command
{
    protected $signature = 'z_:stat';
    protected $description = '命令集合';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $action = $this->choice('请选择动作类型', [
            'init_stat_daily_trade' => '初始化 init_stat_daily_trade 数据',
        ]);

        $todayStartStr = date('Y-m-d');
        $todayStartAt  = strtotime($todayStartStr);

        switch ($action) {
//            case 'init_stat_daily_recharge':
//                $statFirstRecord = rep()->statDailyRecharge->getQuery()->where('date', $todayStartStr)->first();
//                if (!$statFirstRecord) {
//                    $this->error('没找到 %s 数据不予补充, 如有需要请先手动 insert 数据');
//                }
//                $recordCreatedAt = $statFirstRecord->getRawOriginal('created_at');
//                $rechargeBuilder = rep()->tradePay->getQuery()->from('trade_pay', 'tp')->select('ud.reg_os',
//                    DB::raw('SUM(amount) as amount'))->join('user_detail as ud', 'ud.user_id', 'tp.user_id')
//                    ->where('tp.amount', '>', 0)->where('tp.created_at', '>=', $todayStartAt)
//                    ->where('tp.created_at', '<', $recordCreatedAt)
//                    ->where('tp.trade_no', 'not like', '100000%')->groupBy('ud.reg_os');
//
//                $todayRechargeTotals  = (clone $rechargeBuilder)->get();
//                $todayNewUserTotals   = (clone $rechargeBuilder)->where('ud.created_at', '>=', $todayStartAt)->get();
//                $todayOldUserTotals   = (clone $rechargeBuilder)->where('ud.created_at', '<', $todayStartAt)->get();
//                $todayManUserTotals   = (clone $rechargeBuilder)->join('user', 'user.id', 'ud.user_id')
//                    ->where('user.gender', User::GENDER_MAN)->get();
//                $todayWomanUserTotals = (clone $rechargeBuilder)->join('user', 'user.id', 'ud.user_id')
//                    ->where('user.gender', User::GENDER_WOMEN)->get();
//                $appends              = $this->appendFieldsByOs($todayRechargeTotals, 'top_up_total');
//                $appends              = $this->appendFieldsByOs($todayNewUserTotals, 'new_user_total', $appends);
//                $appends              = $this->appendFieldsByOs($todayOldUserTotals, 'old_user_total', $appends);
//                $appends              = $this->appendFieldsByOs($todayManUserTotals, 'man_total', $appends);
//                $appends              = $this->appendFieldsByOs($todayWomanUserTotals, 'woman_total', $appends);
//                foreach ($appends as $os => $append) {
//                    rep()->statDailyRecharge->getQuery()->where('os', $os)
//                        ->where('date', $todayStartStr)->update($append);
//                }
//                break;
            case 'init_stat_daily_trade' :
                $query = rep()->tradePay->getQuery()->join('goods', 'goods.id', 'trade_pay.good_id')
                    ->select(DB::raw('UNIX_TIMESTAMP(FROM_UNIXTIME(trade_pay.created_at, "%Y-%m-%d")) as created_at'),
                        DB::raw('MIN(goods.id) as good_id'), 'goods.type', DB::raw('SUM(amount) as amount'))
                    ->where('trade_pay.created_at', '<', $todayStartAt)->groupBy('goods.type')
                    ->groupByRaw('FROM_UNIXTIME(trade_pay.created_at,"%Y-%m-%d")')->orderBy('created_at');
                if (app()->environment('production')) {
                    $query = $query->where('trade_pay.trade_no', 'not like', '100000%');
                }

                $trades = $query->get();
                foreach ($trades as $trade) {
                    pocket()->statDailyTrade->statRecharge($trade);
                }

                $withdraws = rep()->tradeWithdraw->getQuery()
                    ->select(DB::raw('UNIX_TIMESTAMP(FROM_UNIXTIME(created_at, "%Y-%m-%d")) as created_at'),
                        DB::raw('SUM(ori_amount) as ori_amount'))->where('created_at', '<', $todayStartAt)
                    ->get();
                foreach ($withdraws as $withdraw) {
                    pocket()->statDailyTrade->statWithdraw($withdraw);
                }

                break;
        }
    }

//    public function appendFieldsByOs($parameters, $field, $appends = [], $needAll = true)
//    {
//        $allAppend = 0;
//        foreach ($parameters as $parameter) {
//            $appends[$parameter->reg_os][$field] = DB::raw($field . ' + ' . $parameter->amount);
//            $allAppend                           += $parameter->amount;
//        }
//
//        if ($needAll) {
//            $appends['all'][$field] = DB::raw($field . ' + ' . $allAppend);
//        }
//
//        return $appends;
//    }
}

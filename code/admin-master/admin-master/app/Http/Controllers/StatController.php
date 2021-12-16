<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 统计控制器
 * Class StatController
 * @package App\Http\Controllers\Admin
 */
class StatController extends BaseController
{
    /**
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statDailyNewUser(Request $request)
    {
        $date  = $request->date ?: date('Y-m-d');
        $stats = rep()->statDailyNewUser->m()->where('date', $date)->get();
        if (!$stats->count()) {
            return api_rr()->getOK([], $date . ' 暂时没有统计数据');
        }

        foreach ($stats as $stat) {
            $stat->setAttribute('top_up_rate',
                sprintf("%.0f%%", $stat->new_user_count
                    ? $stat->new_recharge_count / $stat->new_user_count * 100
                    : 0));
        }

        return api_rr()->getOK($stats);
    }

    /**
     * 按天统计邀请表
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statDailyInvite(Request $request)
    {
        $date  = $request->date ?: date('Y-m-d');
        $stats = rep()->statDailyInvite->m()->where('date', $date)->get();
        if (!$stats->count()) {
            return api_rr()->getOK([], $date . ' 暂时没有统计数据');
        }

        return api_rr()->getOK($stats);
    }

    /**
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statDailyTrade(Request $request)
    {
        $date = $request->date ?: date('Y-m-d');
        $stat = rep()->statDailyTrade->m()->where('date', $date)->first();
        if (!$stat) {
            return api_rr()->getOK([], $date . ' 暂时没有统计数据');
        }

        $stat->setAttribute('type', 'today');

        $all   = rep()->statDailyTrade->m()
            ->select(DB::raw('SUM(recharge_total) as recharge_total'), DB::raw('SUM(alipay_total) as alipay_total'),
                DB::raw('SUM(wechat_total) as wechat_total'), DB::raw('SUM(iap_total) as iap_total'),
                DB::raw('SUM(invite_withdraw) as invite_withdraw'),
                DB::raw('SUM(income_withdraw) as income_withdraw'))->get();
        $total = $all->first();
        $total->setAttribute('type', 'all');

        return api_rr()->getOK([$stat, $total]);
    }

    /**
     * 按天统计会员
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statDailyMember(Request $request)
    {
        $date  = $request->date ?: date('Y-m-d');
        $stats = rep()->statDailyMember->m()->where('date', $date)->get();
        if (!$stats->count()) {
            return api_rr()->getOK([], $date . ' 暂时没有统计数据');
        }

        return api_rr()->getOK($stats);
    }

    /**
     * 按天统计充值
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statDailyRecharge(Request $request)
    {
        $date  = $request->date ?: date('Y-m-d');
        $stats = rep()->statDailyRecharge->m()->where('date', $date)
            ->orderByRaw(DB::raw('FIELD(os, "ios", "android", "all")'))->get();
        if (!$stats->count()) {
            return api_rr()->getOK([], $date . ' 暂时没有统计数据');
        }

        return api_rr()->getOK($stats);
    }

    /**
     * 按天统计消费
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statDailyConsume(Request $request)
    {
        $date  = $request->date ?: date('Y-m-d');
        $stats = rep()->statDailyConsume->m()->where('date', $date)->get();
        if (!$stats->count()) {
            return api_rr()->getOK([], $date . ' 暂时没有统计数据');
        }

        return api_rr()->getOK($stats);
    }

    /**
     * 按天统计活跃
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statDailyActive(Request $request)
    {
        $date = $request->date ?: date('Y-m-d');
        $stat = rep()->statDailyActive->m()->where('date', $date)->first();
        if (!$stat) {
            return api_rr()->getOK([], $date . ' 暂时没有统计数据');
        }

        return api_rr()->getOK($stat);
    }
}

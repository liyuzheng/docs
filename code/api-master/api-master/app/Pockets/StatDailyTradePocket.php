<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\Good;
use App\Models\TradePay;
use App\Models\TradeWithdraw;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class StatDailyTradePocket extends BasePocket
{
    /**
     * 获取或创建当日的统计记录
     *
     * @param          $todayStartAt
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    private function getOrCreateRecord($todayStartAt)
    {
        $lock = new RedisLock(Redis::connection(), 'lock:create_stat_daily_trade_record', 3);
        $lock->block(3, function () use ($todayStartAt) {
            $recordCount = rep()->statDailyTrade->getQuery()->where('date', $todayStartAt)->count();
            if (!$recordCount) {
                rep()->statDailyTrade->getQuery()->create(['date' => $todayStartAt]);
            }
        });
    }

    /**
     * 充值渠道统计
     *
     * @param  TradePay  $tradePay
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function statRecharge(TradePay $tradePay)
    {
        if (substr($tradePay->trade_no, 0, 6) == '100000' && $tradePay->os == 100
            && app()->environment('production')) {
            return;
        }

        $todayStartAt = date('Y-m-d', $tradePay->getRawOriginal('created_at'));
        $this->getOrCreateRecord($todayStartAt);
        $good = rep()->good->getQuery()->find($tradePay->good_id);
        if ($good->getRawOriginal('platform') == Good::PLATFORM_APPLE) {
            $channelFiled = 'iap_total';
        } else {
            $channelFiled = $good->getRawOriginal('type') == Good::TYPE_ALIPAY
            || $good->getRawOriginal('type') == Good::TYPE_ALIPAY_WAP
                ? 'alipay_total' : 'wechat_total';
        }

        $update = [
            $channelFiled    => DB::raw($channelFiled . ' + ' . $tradePay->amount),
            'recharge_total' => DB::raw('recharge_total + ' . $tradePay->amount),
        ];

        rep()->statDailyTrade->getQuery()->where('date', $todayStartAt)
            ->update($update);
    }

    /**
     * 提现统计
     *
     * @param  TradeWithdraw  $withdraw
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function statWithdraw(TradeWithdraw $withdraw)
    {
        $todayStartAt = date('Y-m-d', $withdraw->getRawOriginal('created_at'));
        $this->getOrCreateRecord($todayStartAt);
        $inviteFiled = $withdraw->getRawOriginal('type') == TradeWithdraw::TYPE_INCOME
            ? 'income_withdraw' : 'invite_withdraw';

        $update = [
            $inviteFiled =>
                DB::raw($inviteFiled . ' + ' . $withdraw->getRawOriginal('ori_amount'))
        ];

        rep()->statDailyTrade->getQuery()->where('date', $todayStartAt)
            ->update($update);
    }
}

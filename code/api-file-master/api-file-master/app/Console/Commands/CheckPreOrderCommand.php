<?php


namespace App\Console\Commands;


use App\Jobs\UnlockPreOrderRefundJob;
use App\Models\UnlockPreOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckPreOrderCommand extends Command
{
    protected $signature = 'xiaoquan:check_pre_order {duration}';
    protected $description = '检查解锁未完成的订单';

    public function handle()
    {
        $duration   = $this->argument('duration');
        $currentNow = time();
        $expiredAt  = $currentNow + $duration;
        $orders     = rep()->unlockPreOrder->getQuery()->where('user_trigger_at', '>', 0)
            ->where('expired_at', '<=', $expiredAt)
            ->where('done_at', 0)->where('status', '!=', UnlockPreOrder::STATUS_REFUND)
            ->get();

        foreach ($orders as $order) {
            $delay = $order->getRawOriginal('expired_at') - $currentNow;
            if ($delay <= 0) {
                $unlockPreOrderRefundJob = new UnlockPreOrderRefundJob($order->id);
            } else {
                $unlockPreOrderRefundJob = (new UnlockPreOrderRefundJob($order->id))
                    ->delay(Carbon::now()->addSeconds($delay));
            }

            dispatch($unlockPreOrderRefundJob)
                ->onQueue('unlock_pre_order_refund');
        }
    }
}

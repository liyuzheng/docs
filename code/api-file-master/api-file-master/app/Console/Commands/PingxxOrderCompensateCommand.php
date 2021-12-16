<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Pingpp\Charge;
use Pingpp\Order;
use Pingpp\Pingpp;

class PingxxOrderCompensateCommand extends Command
{
    protected $signature = 'xiaoquan:pingxx_order_compensate {start_at} {end_at}';
    protected $description = '更新下载地址脚本,配合spug自动发布做';

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $startAt = $this->argument('start_at');
        $endAt   = $this->argument('end_at');

        $trades  = rep()->tradePay->getQuery()->select('trade_pay.id', 'trade_pay.order_no', 'pay_data.request_param')
            ->where('trade_pay.created_at', '>=', $startAt)
            ->where('trade_pay.created_at', '<', $endAt)->where('trade_pay.done_at', 0)
            ->join('pay_data', 'trade_pay.data_id', 'pay_data.id')->get();

        if ($trades->count()) {
            Pingpp::setApiKey(config('custom.pay.pingxx.base.app_key'));
            Pingpp::setPrivateKeyPath(storage_path('secret/ping++_rsa_private_key.pem'));

            foreach ($trades as $trade) {
                try {
                    $this->info(sprintf('开始查询订单号为 %s 的订单状态----', $trade->order_no));
                    $order    = json_decode($trade->request_param, true);
                    $charge   = Charge::retrieve($order['id']);
                    $tradePay = rep()->tradePay->getQuery()->find($trade->id);
                    if ($charge->offsetGet('paid') && !$tradePay->done_at) {
                        $this->info(sprintf('订单号为 %s 的订单状态为已支付未处理开始修复订单数据', $trade->order_no));
                        $callbackData['data']['object']['transaction_no'] = $charge->offsetGet('transaction_no');
                        $user = rep()->user->getQuery()->find($tradePay->user_id);
                        DB::transaction(function () use ($callbackData, $user, $tradePay) {
                            pocket()->tradePay->processPingXxOrder($tradePay, $user, $callbackData);
                        });
                        $this->info(sprintf('订单号为 %s 的订单修复数据成功', $tradePay->order_no));
                    } else {
                        $this->info(sprintf('订单号为 %s 的订单状态为未支付或已处理, 跳过该笔订单-------', $trade->order_no));
                    }
                } catch (\Exception $exception) {
                    $this->error(sprintf('订单编号为 %s 的订单修复数据失败: %s',
                        $trade->order_no, $exception->getMessage()));
                    d($exception);
                }
            }
        }

    }
}

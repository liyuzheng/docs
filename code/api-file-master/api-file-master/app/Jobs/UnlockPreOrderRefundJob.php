<?php


namespace App\Jobs;


use App\Constant\NeteaseCustomCode;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\TradeBalance;
use App\Models\UnlockPreOrder;
use App\Models\UserRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Pockets\GIOPocket;

/**
 * Class UnlockPreOrderRefundJob
 * @package App\Jobs
 */
class UnlockPreOrderRefundJob extends Job
{
    protected $orderId;

    /**
     * UnlockPreOrderRefundJob constructor.
     *
     * @param $orderId
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle()
    {
        $refundResp = DB::transaction(function () {
            $order = rep()->unlockPreOrder->getQuery()->lockForUpdate()->find($this->orderId);
            if ($order->getRawOriginal('status') != UnlockPreOrder::STATUS_REFUND
                && $order->getRawOriginal('done_at') == 0) {
                $expiredAt = $order->getRawOriginal('expired_at');
                if ($expiredAt <= time() || abs(($expiredAt - time())) <= 10) {
                    $tradeBuy      = rep()->tradeBuy->getQuery()->find($order->buy_id);
                    $consumeAmount = $tradeBuy->getRawOriginal('ori_amount');
                    $tradeBalance  = pocket()->tradeBalance->createRecord($order->user_id, $order,
                        $consumeAmount, TradeBalance::RELATED_TYPE_REFUND);
                    pocket()->trade->batchCreateTradeRecord($tradeBalance);
                    rep()->wallet->getQuery()->where('user_id', $order->user_id)
                        ->increment('balance', $consumeAmount);

                    $order->update(['status' => UnlockPreOrder::STATUS_REFUND]);
                    rep()->userRelation->getQuery()->where('user_id', $order->user_id)
                        ->where('target_user_id', $order->target_user_id)
                        ->where('type', UserRelation::TYPE_PRIVATE_CHAT)->delete();

                    return ResultReturn::success($tradeBuy);
                }
            }

            return ResultReturn::failed('订单已完成或已退款');
        });

        if ($refundResp->getStatus()) {
            $tradeBuy = $refundResp->getData();
            pocket()->mongodb->incrRefundLockedAmount($tradeBuy->user_id,
                $tradeBuy->getRawOriginal('ori_amount'));
            $userDetail = rep()->userDetail->getQuery()->find($tradeBuy->user_id);
            $users      = rep()->user->getQuery()->whereIn('id',
                [$tradeBuy->user_id, $tradeBuy->target_user_id])->get();
            $targetUser = $users->where('id', $tradeBuy->target_user_id)->first();
            $user       = $users->where('id', $tradeBuy->user_id)->first();
            $sender     = pocket()->common->getSystemHelperByAppName($userDetail->client_name);

            $message = sprintf(trans('messages.give_back_chat_diamond_tmpl', [], $user->language),
                $targetUser->nickname);
            pocket()->netease->msgSendMsg($sender, $user->uuid, $message);
            pocket()->gio->report($user->uuid, GIOPocket::EVENT_DIAMOND_REFUND_SUCCESS, ['paymentMoney_var' => $tradeBuy->getRawOriginal('ori_amount')]);

//            $body = [
//                'type' => NeteaseCustomCode::ALERT_IMAGE_MESSAGE,
//                'data' => [
//                    'show_type' => 1,
//                    'icon'      => cdn_url('uploads/common/diamond_refund.png'),
//                    'message'   => sprintf('你解锁的「%s」的私聊未得到回应「%d钻石」已退还到你的钱包～',
//                        $targetUser->nickname, $tradeBuy->ori_amount),
//                ]
//            ];
//
//            $resp = pocket()->netease->msgSendCustomMsg($sender, $user->uuid, $body,
//                ['option' => ['push' => false, 'badge' => false]]);
        }
    }
}

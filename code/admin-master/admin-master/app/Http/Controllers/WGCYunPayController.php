<?php

namespace App\Http\Controllers;

use App\Models\Repay;
use App\Models\UserContact;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Wgc\WgcRequest;

/**
 * 云账户
 * Class WGCYunPayController
 * @package App\Http\Controllers\Admin
 */
class WGCYunPayController extends BaseController
{
    /**
     * 主动打款的逻辑
     *
     * @param  WgcRequest  $request
     *
     * @return JsonResponse
     */
    public function repay(WgcRequest $request) : JsonResponse
    {
        $operatorId      = $this->getAuthAdminId();
        $tradeWithdrawId = request('trade_withdraw_id');
        //        $type            = request('type', 'alipay');
        $pre = pocket()->wgcYunPay->preRepay($tradeWithdrawId);

        if (!$pre->getStatus()) {
            return api_rr()->forbidCommon($pre->getMessage());
        }
        $result = pocket()->wgcYunPay->alipay($tradeWithdrawId, $operatorId);
        //        if ($type === 'alipay') {
        //            $result = pocket()->wgcYunPay->alipay($tradeWithdrawId, $operatorId);
        //        } elseif ($type === 'bankcard') {
        //            $result = pocket()->wgcYunPay->bankcard($tradeWithdrawId, $operatorId);
        //        }
        if ($result->getStatus()) {
            return api_rr()->getOK($result->getData());
        }

        return api_rr()->forbidCommon($result->getMessage());
    }

    /**
     * 某个提现的打款记录
     *
     * @param  WgcRequest  $request
     *
     * @return JsonResponse
     */
    public function repayIndex(WgcRequest $request) : JsonResponse
    {
        $tradeWithdrawId = request('trade_withdraw_id');
        $repay           = rep()->repay->m()
            ->where('related_type', Repay::RELATED_TYPE_WGC)
            ->where('related_id', $tradeWithdrawId)
            ->get();

        $repayData = rep()->repayData->m()->select(['repay_id', 'callback'])
            ->whereIn('repay_id', $repay->pluck('id')->toArray())
            ->get();
        foreach ($repay as $pay) {
            $tmp = $repayData->whereIn('repay_id', $pay->id);
            foreach ($tmp as $row) {
                $row->callback = json_decode($row->callback, true);
            }
            $pay->callback = $tmp;
            $pay->request  = json_decode($pay->request, true);
            $pay->response = json_decode($pay->response, true);
        }

        return api_rr()->getOK($repay);
    }

    /**
     * 获取账户余额
     * @throws \Exception
     */
    public function getBalance()
    {
        $result = pocket()->wgcYunPay->getBalance();

        return api_rr()->getOK($result);
    }
}

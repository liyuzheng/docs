<?php


namespace App\Pockets;


use App\Constant\PayBusinessParam;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\TradePay;
use Pingpp\Charge;

class PayDataPocket extends BasePocket
{
    /**
     * 创建苹果订单的支付凭证数据留存
     *
     * @param  string  $evidence
     * @param  string  $password
     * @param  array   $receipt
     * @param  bool    $stanbox
     *
     * @return \App\Models\PayData
     */
    public function createPayDataByAppleOrder(string $evidence, string $password, array $receipt, bool $stanbox)
    {
        $payData = [
            'request_param'  => json_encode(['receipt-data' => $evidence, 'password' => $password]),
            'callback_param' => json_encode($receipt),
            'done_at'        => time(),
            'request_uri'    => $stanbox ? PayBusinessParam::APPLE_SANDBOX_VERIFY_URL
                : PayBusinessParam::APPLE_VERIFY_URL
        ];

        return rep()->payData->getQuery()->create($payData);
    }

    /**
     * 创建 Google 订单的支付凭证数据留存
     *
     * @param  string                                            $package
     * @param  string                                            $productId
     * @param  string                                            $token
     * @param  \Google_Service_AndroidPublisher_ProductPurchase  $product
     *
     * @return \App\Models\PayData
     */
    public function createPayDataByGoogleOrder(string $package, string $productId, string $token, $product)
    {
        $payData = [
            'request_param'  => json_encode(['packageName' => $package, 'productId' => $productId, 'token' => $token]),
            'callback_param' => json_encode($product),
            'done_at'        => time(),
        ];

        return rep()->payData->getQuery()->create($payData);
    }

    /**
     * 创建 ping++ 订单的支付凭证数据留存
     *
     * @param  Charge  $charge
     *
     * @return \App\Models\PayData
     */
    public function createPayDataByPingXxCharge(Charge $charge)
    {
        $payData = ['request_param' => json_encode($charge),];

        return rep()->payData->getQuery()->create($payData);
    }
}

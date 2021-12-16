<?php


namespace App\Pockets;

use App\Models\Repay;
use WGCYunPay\Config;
use WGCYunPay\Util\RSAUtil;
use App\Models\UserContact;
use WGCYunPay\Util\StringUtil;
use WGCYunPay\Service\PayService;
use WGCYunPay\Service\Des3Service;
use WGCYunPay\Data\Pay\AliPayData;
use WGCYunPay\Service\OrderService;
use WGCYunPay\Data\Pay\BankPayData;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use WGCYunPay\Service\AuthenticationService;

/**
 * 云账户
 * Class WgcYunPayPocket
 * @package App\Pockets
 */
class WgcYunPayPocket extends BasePocket
{
    /**
     * 银行卡打款
     *
     * @param  int  $tradeWithdrawId
     *
     * @return ResultReturn|bool
     */
    public function bankcard(int $tradeWithdrawId, $operatorId = 0)
    {
        $time = time();
        $pre  = $this->preRepay($tradeWithdrawId);
        if (!$pre->getStatus()) {
            return ResultReturn::failed($pre->getMessage());
        }
        [$trans, $contact] = $pre->getData();
        $mess      = $this->wgc_rand();
        $requestId = $this->wgc_rand();
        /** @var int 默认是交易id $orderId */
        if (config('custom.wgc.can_repay')) {
            $orderId = date('YmdHis', time()) . random_int(1000, 9999);
        } else {
            $orderId = $trans->id;
        }
        $repayId = $this->recordRequest($trans, $contact, $time, $mess, $requestId, $orderId, $operatorId);
        if (!$repayId) {
            return ResultReturn::failed('创建支付记录失败');
        }
        try {
            $config                  = $this->getConfig($mess, $requestId);
            $bankPayData             = new BankPayData();
            $bankPayData->order_id   = (string)$orderId;
            $bankPayData->real_name  = $contact->name;
            $bankPayData->id_card    = $contact->id_card;
            $bankPayData->card_no    = $contact->account;
            $bankPayData->pay        = (string)(round($trans->amount / 100, 2));
            $bankPayData->pay_remark = "小圈提现";

            $goPay  = new PayService($config, $bankPayData);
            $result = $goPay->execute();
        } catch (\Exception $exception) {
            $this->recordResponse($repayId, $exception->getMessage());

            return ResultReturn::failed($exception->getMessage());
        }

        $this->recordResponse($repayId, $result);
        if (isset($result['code']) && $result['code'] == '0000') {
            return ResultReturn::success('请求成功~');
        }

        return ResultReturn::success('创建云账户支付失败:' . ($result['message'] ?? ""));
    }

    /**
     * 支付宝打款
     *
     * @param  int  $tradeWithdrawId
     *
     * @return ResultReturn|bool
     */
    public function alipay(int $tradeWithdrawId, $operatorId = 0)
    {
        $time = time();
        $pre  = $this->preRepay($tradeWithdrawId, UserContact::PLATFORM_ALIPAY);
        if (!$pre->getStatus()) {
            return ResultReturn::failed($pre->getMessage());
        }
        [$trans, $contact] = $pre->getData();
        $mess      = $this->wgc_rand();
        $requestId = $this->wgc_rand();
        /** @var int 默认是交易id $orderId */
        if (config('custom.wgc.can_repay')) {
            $orderId = date('YmdHis', time()) . random_int(1000, 9999);
        } else {
            $orderId = $trans->id;
        }
        $repayId = $this->recordRequest($trans, $contact, $time, $mess, $requestId, $orderId, $operatorId);
        if (!$repayId) {
            return ResultReturn::failed('创建支付记录失败');
        }
        try {
            $config                 = $this->getConfig($mess, $requestId);
            $aliPayData             = new AliPayData();
            $aliPayData->order_id   = (string)$orderId;
            $aliPayData->real_name  = $contact->name;
            $aliPayData->id_card    = $contact->id_card;
            $aliPayData->pay        = (string)(round($trans->amount / 100, 2));
            $aliPayData->pay_remark = "小圈提现";
            $aliPayData->card_no    = $contact->account;
            $aliPayData->check_name = "Check";

            $goPay  = new PayService($config, $aliPayData);
            $result = $goPay->execute();
        } catch (\Exception $exception) {
            $this->recordResponse($repayId, $exception->getMessage());

            return ResultReturn::failed($exception->getMessage());
        }

        $this->recordResponse($repayId, $result);
        if (isset($result['code']) && $result['code'] == '0000') {
            return ResultReturn::success('请求成功~');
        }

        return ResultReturn::success('创建云账户支付失败:' . ($result['message'] ?? ""));
    }

    /**
     * 获取配置
     *
     * @param $mess
     * @param $requestId
     *
     * @return Config
     */
    public function getConfig($mess, $requestId) : Config
    {
        $config = new Config();
        //商户ID   登录云账户综合服务平台在商户中心-》商户管理-》对接信息中查看
        $config->dealer_id = config('custom.wgc.dealer_id');
        //综合服务主体ID   登录云账户综合服务平台在商户中心-》商户管理-》对接信息中查看
        $config->broker_id = config('custom.wgc.broker_id');
        //商户app key   登录云账户综合服务平台在商户中心-》商户管理-》对接信息中查看
        $config->app_key = config('custom.wgc.app_key');
        //商户3des key   登录云账户综合服务平台在商户中心-》商户管理-》对接信息中查看
        $config->des3_key = config('custom.wgc.des3_key');
        //商户私钥  商户使用OpenSSL自行生成的RSA2048秘钥 ，生成的商户公钥需要配置在云账户综合服务平台在商户中心-》商户管理-》对接信息-》商户公钥
        $config->private_key = config('custom.wgc.private_key');
        //云账户公钥 登录云账户综合服务平台在商户中心-》商户管理-》对接信息中查看（每个商户ID对应的云账户公钥不同）
        $config->public_key = config('custom.wgc.public_key');
        $config->mess       = $mess;
        $config->timestamp  = time();
        $config->request_id = $requestId;

        return $config;
    }

    /**
     * 生成唯一id
     * @return string
     */
    public function wgc_rand() : string
    {
        return StringUtil::round(10);
    }

    /**
     * 记录请求记录
     *
     * @param $trans
     * @param $contact
     * @param $time
     * @param $mess
     * @param $requestId
     * @param $orderId
     * @param $operatorId
     *
     * @return int
     */
    public function recordRequest($trans, $contact, $time, $mess, $requestId, $orderId, $operatorId) : int
    {
        $repayId = 0;
        try {
            $repayId = rep()->repay->m()
                ->insertGetId([
                    'user_id'      => $trans->user_id,
                    'related_type' => Repay::RELATED_TYPE_WGC,
                    'related_id'   => $trans->id,
                    'order_id'     => $orderId,
                    'status'       => Repay::STATUS_DEFAULT,
                    'request_id'   => $requestId,
                    'request'      => json_encode([
                        'name'      => $contact->name,
                        'id_card'   => $contact->id_card,
                        'account'   => $contact->account,
                        'mobile'    => $contact->mobile,
                        'amount'    => $trans->amount,
                        'requestId' => $requestId,
                        'mess'      => $mess,
                    ]),
                    'mess'         => $mess,
                    'amount'       => $trans->amount,
                    'operator_id'  => $operatorId,
                    'created_at'   => $time,
                    'updated_at'   => $time,
                ]);
        } catch (\Exception $exception) {

        }

        return $repayId;
    }

    /**
     * 记录响应
     *
     * @param $repayId
     * @param $result
     */
    public function recordResponse($repayId, $result)
    {
        rep()->repay->m()->where('id', $repayId)->update([
            'response' => json_encode($result)
        ]);
    }

    /**
     * 验证签名
     *
     * @param  array  $notifyData
     *
     * @return array
     */
    public function verify(array $notifyData) : array
    {
        $config       = pocket()->wgcYunPay->getConfig(StringUtil::round(10), StringUtil::round(10));
        $datainfo     = Des3Service::decode($notifyData['data'], $config->des3_key);
        $result       = new RSAUtil($config);
        $verifyResult = $result->verify($notifyData);

        return [
            $datainfo,
            (int)$verifyResult
        ];
    }

    /**
     * 某笔是否已经成功打过款，或者正在处理中
     *
     * @param  int  $transId
     *
     * @return bool
     */
    public function whetherHasRepay(int $transId) : bool
    {
        return rep()->repay->m()->where('related_id', $transId)
            ->whereIn('status', [Repay::STATUS_DEFAULT, Repay::STATUS_SUCCESS])
            ->exists();
    }

    /**
     * 打款的前置条件
     *
     * @param       $tradeWithdrawId
     * @param  int  $platform
     *
     * @return ResultReturn
     * @throws \Exception
     */
    public function preRepay($tradeWithdrawId, $platform = UserContact::PLATFORM_BANK_CARD) : ResultReturn
    {
        $trans = rep()->tradeWithdraw->m()
            ->where('id', $tradeWithdrawId)
            ->first();
        if (!$trans) {
            return ResultReturn::failed('交易不存在');
        }
        if ($trans->created_at->timestamp <= strtotime('2021-02-04')) {
            return ResultReturn::failed('历史交易不允许打款~');
        }
        if ($trans->done === 0) {
            return ResultReturn::failed('请先同意提现后，再打款');
        }
        $contact = rep()->userContact->m()
            ->where('id', $trans->contact_id)
            ->where('platform', $platform)
            ->first();
        if (!$contact || !$contact->name || !$contact->id_card || !$contact->account || !$trans->amount) {
            return ResultReturn::failed('用户信息不完整');
        }
        if (!config('custom.wgc.can_repay') && $this->whetherHasRepay($trans->id)) {
            return ResultReturn::failed('该笔支付已经打款过~');
        }
        $result  = $this->getBalance();
        $balance = 0;
        if ($platform == UserContact::PLATFORM_BANK_CARD) {
            $balance = $result['bank_card_balance'] ?? 0;
        } elseif ($platform == UserContact::PLATFORM_ALIPAY) {
            $balance = $result['alipay_balance'] ?? 0;
        }
        if ($trans->amount / 100 > $balance) {
            return ResultReturn::failed('云账户余额不足，请充值后再打款~');
        }
        if (!$result['is_bank_card']) {
            return ResultReturn::failed('云账户银行卡不可用，请联系开发人员~');
        }

        return ResultReturn::success([
            $trans, $contact
        ]);
    }

    /**
     * 获取余额
     * @return array
     * @throws \Exception
     */
    public function getBalance() : array
    {
        $config          = $this->getConfig(StringUtil::round(10), StringUtil::round(10));
        $accounts        = new OrderService($config);
        $result          = $accounts
            ->setMethodType("query-accounts")
            ->execute();
        $cardInfo        = $result['data']['dealer_infos'] ?? [];
        $bankCardBalance = $cardInfo[0]['bank_card_balance'] ?? 0;
        $alipayBalance   = $cardInfo[0]['alipay_balance'] ?? 0;
        $wxpayBalance    = $cardInfo[0]['wxpay_balance'] ?? 0;
        $isBankCard      = $cardInfo[0]['is_bank_card'] ?? false;
        $isAlipay        = $cardInfo[0]['is_alipay'] ?? false;
        $isWxpay         = $cardInfo[0]['is_wxpay'] ?? false;

        return [
            'bank_card_balance' => $bankCardBalance,
            'alipay_balance'    => $alipayBalance,
            'wxpay_balance'     => $wxpayBalance,
            'is_bank_card'      => $isBankCard,
            'is_alipay'         => $isAlipay,
            'is_wxpay'          => $isWxpay
        ];
    }

    /**
     * 验证身份证和姓名是否一致
     *
     * @param $truename
     * @param $idCard
     *
     * @return ResultReturn
     * @throws \Exception
     */
    public function checkIdCardAndName($truename, $idCard) : ResultReturn
    {
        if (!app()->environment('production')) {
            return ResultReturn::success([]);
        }
        $config    = $this->getConfig(StringUtil::round(10), StringUtil::round(10));
        $verify    = new AuthenticationService($config);
        $verifyRes = $verify
            ->setRealName($truename)
            ->setIdCard($idCard)
            ->setMethodType("verify_id")
            ->execute();
        if (isset($verifyRes['code']) && $verifyRes['code'] === '0000') {
            return ResultReturn::success([]);
        }

        return ResultReturn::failed(json_encode($verifyRes));
    }
}

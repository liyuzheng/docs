<?php


namespace App\Pockets;


use App\Constant\NeteaseCustomCode;
use App\Constant\PayBusinessParam;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Foundation\Handlers\Tools;
use App\Models\Discount;
use App\Models\Good;
use App\Models\MemberRecord;
use App\Models\PayChannel;
use App\Models\PayData;
use App\Models\Task;
use App\Models\Trade;
use App\Models\TradePay;
use App\Models\User;
use Google\Service\Exception as GoogleServiceException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Cache\RedisLock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Pingpp\Charge;
use Pingpp\Pingpp;
use App\Jobs\UpdateChannelDataJob;
use Google_Client;

class TradePayPocket extends BasePocket
{
    /**
     * 通过苹果订单信息创建完整的交易订单
     *
     * @param  User     $user
     * @param  array    $order
     * @param  PayData  $payData
     * @param  Good     $good
     *
     * @return ResultReturn
     */
    private function completeTradePayByApple(User $user, PayData $payData, array $order, $good)
    {
        $tradeNo = $order['web_order_line_item_id'] ?? $order['transaction_id'];
        $good    = $good ?: rep()->good->getQuery()->where('product_id', $order['product_id'])
            ->withTrashed()->first();

        $count = rep()->tradePay->getQuery()->where('trade_no', $tradeNo)->where('user_id', $user->id)
            ->where('status', '!=', TradePay::STATUS_FAILED)->count();
        if ($count) {
            return ResultReturn::failed(trans('messages.order_has_expired'));
        }

        $tradePayData            = $this->buildTradePayByUserAndGoods($payData, $user, $good, $tradeNo);
        $tradePayData['os']      = TradePay::OS_IOS;
        $tradePayData['channel'] = TradePay::CHANNEL_APPLE;
        $tradePayData['done_at'] = time();
        $tradePayData['status']  = TradePay::STATUS_SUCCESS;

        $tradePay = rep()->tradePay->getQuery()->create($tradePayData);
        switch ($tradePay->getRawOriginal('related_type')) {
            case TradePay::RELATED_TYPE_RECHARGE_VIP:
                $card             = rep()->card->getQuery()->find($good->related_id);
                $lastMemberRecord = rep()->memberRecord->getQuery()->where('certificate',
                    $order['original_transaction_id'])->orderBy('id', 'desc')->first();
                $memberUser       = $lastMemberRecord && $lastMemberRecord->user_id != $user->id
                    ? rep()->user->getQuery()->find($lastMemberRecord->user_id)
                    : $user;
                $orderExpiredAt   = isset($order['expires_date_ms']) ? substr($order['expires_date_ms'], 0, -3) : 0;
                if ($orderExpiredAt) {
                    $orderStartAt = substr($order['purchase_date_ms'], 0, -3);
                    $duration     = $orderExpiredAt - $orderStartAt;
                } else {
                    $expiredAt = rep()->member->getUserMemberExpiredAt($memberUser->id);
                    $duration  = app()->environment('production') ? $card->getDuration($expiredAt) : 120;
                }

                pocket()->member->createMemberByCard($memberUser, $tradePay, $duration,
                    $order['original_transaction_id'], $orderExpiredAt);
                pocket()->inviteRecord->postBeInviterBuyMember($user, $card);
                break;
        }

        $this->createTradePayAttachedData($tradePay, $user);

        return ResultReturn::success($tradePay);
    }

    /**
     * 根据 Google 订单完成支付订单
     *
     * @param  User                                              $user
     * @param  Good                                              $good
     * @param  PayData                                           $payData
     * @param  \Google_Service_AndroidPublisher_ProductPurchase  $product
     *
     * @return ResultReturn
     */
    private function completeTradePayByGoogle(User $user, Good $good, PayData $payData, $product)
    {
        $tradePayData            = $this->buildTradePayByUserAndGoods($payData, $user, $good, $product->orderId);
        $tradePayData['os']      = TradePay::OS_ANDROID;
        $tradePayData['channel'] = TradePay::CHANNEL_GOOGLE;
        $tradePayData['done_at'] = time();
        $tradePayData['status']  = TradePay::STATUS_SUCCESS;

        $tradePay = rep()->tradePay->getQuery()->create($tradePayData);
        switch ($tradePay->getRawOriginal('related_type')) {
            case TradePay::RELATED_TYPE_RECHARGE_VIP:
                $card      = rep()->card->getQuery()->find($good->related_id);
                $expiredAt = rep()->member->getUserMemberExpiredAt($user->id);
                $duration  = app()->environment('production') ? $card->getDuration($expiredAt) : 120;
                pocket()->member->createMemberByCard($user, $tradePay, $duration);
                pocket()->inviteRecord->postBeInviterBuyMember($user, $card);
                break;
        }

        $this->createTradePayAttachedData($tradePay, $user);

        return ResultReturn::success($tradePay);
    }

    /**
     * 生成用户法币交易 (ping++)
     *
     * @param  PayData     $payData
     * @param  User        $user
     * @param  Good        $good
     * @param  Charge      $charge
     * @param  PayChannel  $channel
     * @param  int         $os
     *
     * @return \App\Models\TradePay|null
     */
    public function createTradePayByPingXx(PayData $payData, User $user, Good $good, Charge $charge, PayChannel $channel, $os)
    {
        $tradePayData               = $this->buildTradePayByUserAndGoods($payData, $user, $good, '');
        $tradePayData['os']         = $os;
        $tradePayData['channel']    = TradePay::CHANNEL_PING_XX;
        $tradePayData['order_no']   = $charge->order_no;
        $tradePayData['channel_id'] = $channel->id;
        $tradePayData['discount']   = $good->discount ?? 1;
        $tradePayData['ori_amount'] = $good->not_discount_price ?? $good->getRawOriginal('price');

        return rep()->tradePay->getQuery()->create($tradePayData);
    }

    /**
     * apple 恢复购买逻辑
     *
     * @param  User          $user
     * @param  MemberRecord  $memberRecord
     * @param  TradePay      $tradePay
     *
     * @return ResultReturn
     */
    private function restoreTradePayMember(User $user, MemberRecord $memberRecord, TradePay $tradePay)
    {
        $currentNow     = time();
        $originalMember = rep()->member->getQuery()->where('user_id', $memberRecord->user_id)->lockForUpdate()->first();
        if (in_array($memberRecord->status, MemberRecord::STATUS_INVALID)
            || $memberRecord->expired_at < $currentNow + 10) {
            return ResultReturn::failed(trans('messages.subscription_expires'));
        }

        $residualDuration = $memberRecord->expired_at - $currentNow > $memberRecord->duration
            ? $memberRecord->duration : $memberRecord->expired_at - $currentNow;

        pocket()->member->inheritMemberRecord($user, $memberRecord, $originalMember,
            $residualDuration, $tradePay->related_id);

        return ResultReturn::success($tradePay);
    }

    /**
     * 通过 ping++ 的回调数据完成订单
     *
     * @param  TradePay  $tradePay
     * @param  array     $callbackData
     * @param  User      $user
     *
     * @return ResultReturn
     */
    private function updateTradeByPingXxCallback(TradePay $tradePay, User $user, array $callbackData)
    {
        $lockTradePay = rep()->tradePay->getQuery()->lockForUpdate()->find($tradePay->id);

        if (!$lockTradePay || $lockTradePay->done_at) {
            return ResultReturn::failed('');
        }

        $updateData = [
            'done_at'  => time(),
            'status'   => TradePay::STATUS_SUCCESS,
            'trade_no' => $callbackData['data']['object']['transaction_no']
        ];

        switch ($tradePay->getRawOriginal('related_type')) {
            case TradePay::RELATED_TYPE_RECHARGE_VIP:
                $card      = rep()->card->getCardByGoodsId($tradePay->good_id);
                $expiredAt = rep()->member->getUserMemberExpiredAt($user->id);
                $duration  = app()->environment('production') ? $card->getDuration($expiredAt) : 120;
                pocket()->member->createMemberByCard($user, $tradePay, $duration);
                pocket()->inviteRecord->postBeInviterBuyMember($user, $card);
                $inviteDiscountTasks = rep()->discount->getQuery()->where('pay_id', $lockTradePay->id)
                    ->where('related_type', Discount::RELATED_TYPE_INVITE_PRIZE)
                    ->pluck('related_id')->toArray();
                $inviteDiscountTasks && rep()->task->getQuery()->whereIn('id', $inviteDiscountTasks)
                    ->update(['status' => Task::STATUS_SUCCEED, 'done_at' => time()]);
                break;
        }

        $tradePay->update($updateData);
        $this->createTradePayAttachedData($tradePay, $user);
        rep()->discount->getQuery()->where('pay_id', $lockTradePay->id)
            ->update(['done_at' => time()]);

        return ResultReturn::success($tradePay);
    }

    /**
     * 创建用户法币订单附属数据，例如：用户会员记录，用户代币交易记录，总流水记录等
     *
     * @param  TradePay  $tradePay
     * @param  User      $user
     */
    private function createTradePayAttachedData(TradePay $tradePay, User $user)
    {
        switch ($tradePay->getRawOriginal('related_type')) {
            case TradePay::RELATED_TYPE_RECHARGE_VIP:
                pocket()->trade->createRecord($user, $tradePay);
                break;
            case TradePay::RELATED_TYPE_RECHARGE:
            default:
                $currency     = rep()->currency->getQuery()->find($tradePay->related_id);
                $tradeBalance = pocket()->tradeBalance->createRecord($user, $tradePay,
                    $currency->getRawOriginal('amount'));
                pocket()->trade->createRecord($user, $tradeBalance);
                rep()->wallet->getQuery()->where('user_id', $user->id)->increment('balance',
                    $currency->getRawOriginal('amount'));
        }
    }

    /**
     * 绑定法币交易不同交易途径相同代参数
     *
     * @param  User          $user
     * @param  Good          $good
     * @param  string        $tradeNo
     * @param  PayData|null  $payData
     *
     * @return array
     */
    public function buildTradePayByUserAndGoods($payData, User $user, Good $good, string $tradeNo)
    {
        return [
            'data_id'      => $payData ? $payData->id : 0,
            'user_id'      => $user->id,
            'good_id'      => $good->id,
            'related_type' => Good::TRADE_PAY_RELATED_TYPES[$good->getRawOriginal('related_type')],
            'related_id'   => $good->related_id,
            'order_no'     => date('YmdHis', time()) . rand(1, 10000),
            'trade_no'     => $tradeNo,
            'amount'       => $good->getRawOriginal('price'),
            'ori_amount'   => $good->getRawOriginal('price'),
        ];
    }

    /**
     * 通过 evidence 从请求苹果获取订单详情信息
     *
     * @param  User    $user
     * @param  string  $evidence
     * @param  string  $password
     * @param  bool    $isSandBox
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function getAppleReceiptByEvidence(User $user, string $evidence, string $password, bool $isSandBox)
    {
        $requestUri = $isSandBox ? PayBusinessParam::APPLE_SANDBOX_VERIFY_URL
            : PayBusinessParam::APPLE_VERIFY_URL;

        return $this->getAppleReceiptByEvidenceAndUrl($user, $evidence, $password, $requestUri);
    }

    /**
     * 通过 uri 和 evidence 请求苹果获取订单详情信息
     *
     * @param  User    $user
     * @param  string  $evidence
     * @param  string  $requestUri
     * @param  string  $password
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function getAppleReceiptByEvidenceAndUrl(User $user, string $evidence, string $password, string $requestUri)
    {
        $options         = ['json' => ['receipt-data' => $evidence, 'password' => $password]];
        $recordLogParams = [
            'target' => 'apple.pay.evidence',
            'params' => ['user_id' => $user->id, 'evidence' => $evidence, 'request_uri' => $requestUri]
        ];

        try {
            $response = Tools::getHttpRequestClient()->post($requestUri, $options);
            $content  = json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $exception) {
            Log::error($exception->getMessage(), $recordLogParams);

            throw $exception;
        }

        $recordLogParams['params']['receipt'] = $content;
        if (!isset($content['receipt']['bundle_id'])
            || !in_array($content['receipt']['bundle_id'], PayBusinessParam::APPLE_BUNDLE_ID)
            || !isset($content['status'])
            || $content['status'] !== 0) {
            Log::error('status或bundle_id校验失败', $recordLogParams);

            return ResultReturn::failed(trans('messages.data_verification_failed'));
        }

        Log::info(sprintf("user_id: %d记录留存", $user->id), $recordLogParams);

        return ResultReturn::success($content);
    }

    /**
     * 生成 ping++ 交易订单
     *
     * @param  User    $user
     * @param  Good    $good
     * @param  string  $clientId
     * @param  string  $clientIp
     * @param  string  $os
     *
     * @return array
     */
    public function generatePingXxOrderByPay(User $user, Good $good, $os, $clientId, $clientIp)
    {
        $extra = [];
        switch ($good->getRawOriginal('type')) {
            case Good::TYPE_ALIPAY_WAP:
                $jumpUrl = $os == 'web' ? 'https://web.wqdhz.com/jump-to-pay'
                    : 'https://share.ruanruan.club/payment/result_html';
                $extra   = [
                    'success_url'          => $jumpUrl . '?type=success',
                    'cancel_url'           => $jumpUrl . '?type=cancel',
                    'disable_pay_channels' => 'creditCard,pcredit,pcreditpayInstallment'
                ];
                break;
            case Good::TYPE_ALIPAY:
                $extra['disable_pay_channels'] = 'creditCard,pcredit,pcreditpayInstallment';
                break;
            case Good::TYPE_WX_WAP:
                $extra = [
                    'result_url' => 'https://web.wqdhz.com/jump-to-pay',
                    'limit_pay'  => 'no_credit',
                ];
                break;
            case Good::TYPE_WECHAT:
            default:
                $extra['limit_pay'] = 'no_credit';
        }

        $payChannel = rep()->payChannel->getPayChannelByRatio($user, PayChannel::TYPE_PINGXX, $os);
        $params     = $payChannel->params;
        Pingpp::setApiKey($params['app_key']);
        Pingpp::setPrivateKeyPath(storage_path('secret/ping++_rsa_private_key.pem'));
        $chargeData = [
            'subject'   => '小圈充值',
            'body'      => 'xiaoquan charge',
            'amount'    => $good->getRawOriginal('price'),
            'order_no'  => date('YmdHis', time()) . rand(1, 10000),
            'currency'  => 'cny',
            'extra'     => $extra,
            'channel'   => Good::GOODS_PAY_METHOD_MAPPING[$good->getRawOriginal('type')],
            'client_ip' => $clientIp,
            'app'       => ['id' => $params['app_id']],
            'metadata'  => ['client_id' => $clientId]
        ];

        return [Charge::create($chargeData), $payChannel];
    }

    /**
     * 获取Google订单
     *
     * @param  string  $package
     * @param  string  $productId
     * @param  string  $token
     *
     * @return ResultReturn
     * @throws \Google\Exception
     */
    public function getProductByGoogleOrder(string $package, string $productId, string $token)
    {
        $httpClient   = new GuzzleClient(['proxy' => config('custom.request_proxy')]);
        $googleClient = new Google_Client();
        $googleClient->setHttpClient($httpClient);
        $googleClient->addScope(\Google_Service_AndroidPublisher::ANDROIDPUBLISHER);
        $googleClient->setAuthConfig(storage_path('secret/service_acccount_credentials.json'));
        $publisher = new \Google_Service_AndroidPublisher($googleClient);

        try {
            $product = $publisher->purchases_products->get($package, $productId, $token);
        } catch (\Exception $exception) {
            $logContext = ['target' => 'google.pay', 'params' => func_get_args()];
            $message    = $exception->getMessage();
            if ($exception instanceof GoogleServiceException) {
                $errors = json_decode($exception->getMessage(), true);
                $errors && isset($errors['error']) && $message = $errors['error']['message'];
                $logContext['errors'] = json_encode($errors);
            }

            Log::error($message, $logContext);

            return ResultReturn::failed('');
        }

        return ResultReturn::success($product);
    }

    /**
     * 校验 ping++ 回调参数代合法性
     *
     * @param  array  $callbackData
     *
     * @return ResultReturn
     */
    public function verifyPingXxCallbackParams(array $callbackData)
    {
        Log::info('ping++ callback retained',
            ['target' => 'pingxx.callback.verify', 'callback_data' => $callbackData]);

        if (!isset($callbackData['type']) || !isset($callbackData['data']['object']['order_no'])) {
            return ResultReturn::failed('数据完整性校验失败!');
        }

        $orderNo = $callbackData['data']['object']['order_no'];
        switch ($callbackData['type']) {
            case 'charge.succeeded':
                $tradePay = rep()->tradePay->write()->where('order_no', $orderNo)->first();
                if (!$tradePay) {
                    if (app()->environment('production')) {
                        pocket()->common->commonQueueMoreByPocketJob(pocket()->dingTalk, 'sendSimpleMessage', [
                            'https://oapi.dingtalk.com/robot/send?access_token=c8d84555a2b745d5786b021c7ea7c3effb7bf09537e2d07bc49779aade28d07c',
                            sprintf('[PINGXX支付回调]-找不到订单编号为 %s 的订单, callback: %s', $orderNo,
                                json_encode($callbackData))
                        ]);
                    }

                    return ResultReturn::failed(sprintf('找不到订单编号为 %s 的订单', $orderNo));
                } elseif ($tradePay->getRawOriginal('done_at') > 0) {
                    return ResultReturn::success(null, sprintf('订单编号为 %s 的订单已完成交易, 重复回调', $orderNo));
                }

                return ResultReturn::success($tradePay);
            default:
                return ResultReturn::failed(sprintf('type %s not support', $callbackData['type']));
        }
    }

    /**
     * 验证苹果回调数据是否正确
     *
     * @param  array  $callbackData
     *
     * @return ResultReturn
     */
    public function verifyAppleCallbackParams(array $callbackData)
    {
        if (!isset($callbackData['password'])
            || !in_array($callbackData['password'], PayBusinessParam::APPLE_PASSWORDS)) {
            return ResultReturn::failed('数据不正确, 请确认请求完整');
        }

        //        $receiptInfos       = ['notification_type' => $callbackData['notification_type'], 'receipt_info' => []];
        //        $currentMillisecond = get_millisecond();
        //
        //        foreach ($callbackData['latest_receipt_info'] as $receiptInfo) {
        //            if ($receiptInfo['expires_date_ms'] > $currentMillisecond) {
        //                $receiptInfos[] = $receiptInfo;
        //            }
        //        }

        return ResultReturn::success($callbackData);
    }

    /**
     * 处理 apple 收据并根据不同点场景处理订单
     *
     * @param  User     $user
     * @param  PayData  $payData
     * @param  array    $orders
     *
     * @throws \Exception
     */
    public function processAppleEvidence(User $user, PayData $payData, array $orders)
    {
        foreach ($orders as $order) {

            try {
                $tradePayResp = $this->processAppleOrder($user, $payData, $order);
            } catch (\Exception $exception) {
                Log::error($exception->getMessage(), [
                    'target' => 'apple.pay',
                    'params' => ['user_id' => $user->id, 'data_id' => $payData->id]
                ]);

                throw $exception;
            }

            if ($tradePayResp->getStatus()) {
                [$tradePay, $restore] = $tradePayResp->getData();
                $this->processTradePayHook($tradePay, $user, !$restore);
            }
        }
    }

    /**
     * 根据交易ID获取订单信息
     *
     * @param  string  $tradeNo
     * @param  array   $orders
     *
     * @return ResultReturn
     */
    public function getAppleSingleOrderByTradeNo(string $tradeNo, array $orders)
    {
        foreach ($orders as $order) {
            if ($tradeNo == $order['transaction_id']) {
                return ResultReturn::success($order);
            }
        }

        return ResultReturn::failed(trans('messages.order_has_expired'));
    }

    /**
     * 处理苹果某一笔具体的订单
     *
     * @param  User     $user
     * @param  PayData  $payData
     * @param  Good     $good
     * @param  array    $order
     *
     * @return ResultReturn|bool
     * @throws \Exception
     */
    public function processAppleSingleOrder(User $user, PayData $payData, Good $good, array $order)
    {
        try {
            $tradePayResp = $this->processAppleOrder($user, $payData, $order, $good);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), [
                'target' => 'single.apple.pay',
                'params' => ['user_id' => $user->id, 'data_id' => $payData->id]
            ]);

            throw $exception;
        }

        if ($tradePayResp->getStatus()) {
            [$tradePay, $restore] = $tradePayResp->getData();
            $this->processTradePayHook($tradePay, $user, !$restore);

            return ResultReturn::success($tradePay);
        }

        return $tradePayResp;
    }

    /**
     * 用户完成支付订单之后的钩子
     *
     * @param  TradePay  $tradePay
     * @param  User      $user
     * @param  bool      $newTrade
     */
    private function processTradePayHook(TradePay $tradePay, User $user, $newTrade = true)
    {
        if ($tradePay->getRawOriginal('related_type') == TradePay::RELATED_TYPE_RECHARGE_VIP) {
            pocket()->user->resetUserPunishment($user->id);
            pocket()->common->commonQueueMoreByPocketJob(pocket()->gio,
                'reportUserPayMember', [$tradePay, $user]);
            pocket()->esUser->updateUserFieldToEs($user->id, ['is_member' => 1]);
            $query = rep()->member->getQuery()->where('created_at', '<', time());
            if (!$user->isMember($query)) {
                pocket()->member->sendUserBecomeMemberMessage($user);
            }
        } else {
            pocket()->common->commonQueueMoreByPocketJob(pocket()->gio,
                'reportUserPayCurrency', [$tradePay, $user]);
        }

        pocket()->common->commonQueueMoreByPocketJob(pocket()->stat,
            'statUserTopUp', [$user->id, $tradePay->id], 10);

        pocket()->common->commonQueueMoreByPocketJob(
            pocket()->account,
            'setNotNeedFakeUser',
            [$user->id]
        );

        if ($newTrade) {
            dispatch(new UpdateChannelDataJob("recharge", $user->id, [
                'ori_amount' => $tradePay->getRawOriginal('amount'),
                'amount'     => $tradePay->getRawOriginal('amount'),
                'done_at'    => time(),
                'order_no'   => $tradePay->order_no,
            ]))->onQueue('update_channel_data');
        }
    }

    /**
     *
     * @param  User       $user
     * @param  Good|null  $good
     * @param  PayData    $payData
     * @param  array      $order
     *
     * @return ResultReturn|bool
     * @throws LockTimeoutException
     */
    private function processAppleOrder(User $user, PayData $payData, array $order, $good = null)
    {
        $tradeNo   = $order['web_order_line_item_id'] ?? $order['transaction_id'];
        $lockKey   = "lock:pay_order:" . $tradeNo;
        $redisLock = new RedisLock(Redis::connection(), $lockKey, 3);

        return $redisLock->block(3,
            function () use ($user, $order, $payData, $tradeNo, $good) {
                $tradePay     = rep()->tradePay->getQuery()->where('trade_no', $tradeNo)->first();
                $tradePayResp = DB::transaction(function () use ($tradePay, $user, $order, $payData, $good) {
                    if (!$tradePay) {
                        return $this->completeTradePayByApple($user, $payData, $order, $good);
                    }

                    return ResultReturn::failed(trans('messages.order_has_expired'));
                    // 恢复购买逻辑
                    /*if ($tradePay) {
                        $tradePayResp = ResultReturn::failed("无效订单");
                        if ($tradePay->related_type == TradePay::RELATED_TYPE_RECHARGE_VIP) {
                            $tradePay     = rep()->tradePay->getQuery()->lockForUpdate()->find($tradePay->id);
                            $memberRecord = rep()->memberRecord->getQuery()->where('pay_id', $tradePay->id)
                                ->whereIn('status', MemberRecord::STATUS_VALID)->first();
                            if ($memberRecord && $memberRecord->expired_at > time() && $memberRecord->user_id != $user->id) {
                                $tradePayResp = $this->restoreTradePayMember($user, $memberRecord, $tradePay);
                            }
                        }
                    } else {
                        $tradePayResp = $this->createCompleteTradePayByApple($user, $payData, $order);
                    }

                    return $tradePayResp;*/
                });

                if ($tradePayResp->getStatus()) {
                    $tradePayResp->setData([$tradePayResp->getData(), $tradePay != null]);
                }

                return $tradePayResp;
            });
    }

    /**
     * 完成 Ping++ 支付订单
     *
     * @param  User      $user
     * @param  TradePay  $tradePay
     * @param  array     $callbackData
     */
    public function processPingXxOrder(User $user, TradePay $tradePay, array $callbackData)
    {
        $tradePayResp = DB::transaction(function () use ($callbackData, $tradePay, $user) {
            $tradePayResp = $this->updateTradeByPingXxCallback($tradePay, $user, $callbackData);
            if ($tradePayResp->getStatus()) {
                rep()->payData->getQuery()->where('id', $tradePayResp->getData()->data_id)->update([
                    'callback_param' => json_encode($callbackData)
                ]);
            }

            return $tradePayResp;
        });

        if ($tradePayResp->getStatus()) {
            $tradePay = $tradePayResp->getData();
            $this->processTradePayHook($tradePay, $user);
        }

        return $tradePayResp;
    }

    /**
     * 完成 Google 订单
     *
     * @param  User                                              $user
     * @param  Good                                              $good
     * @param  PayData                                           $payData
     * @param  \Google_Service_AndroidPublisher_ProductPurchase  $product
     *
     * @return bool
     * @throws LockTimeoutException
     */
    public function processGoogleOrder(User $user, Good $good, PayData $payData, $product)
    {
        $lockKey   = "lock:pay_order:" . $product->orderId;
        $redisLock = new RedisLock(Redis::connection(), $lockKey, 3);

        $tradePayResp = $redisLock->block(3, function () use ($user, $product, $good, $payData) {
            $tradePay = rep()->tradePay->getQuery()->where('trade_no', $product->orderId)->first();
            if (!$tradePay) {
                return DB::transaction(function () use ($user, $product, $good, $payData) {
                    return $this->completeTradePayByGoogle($user, $good, $payData, $product);
                });
            }

            return ResultReturn::failed(trans('messages.order_has_expired'));
        });

        if ($tradePayResp->getStatus()) {
            $tradePay = $tradePayResp->getData();
            $this->processTradePayHook($tradePay, $user);
        }

        return $tradePayResp;
    }

    /**
     * 通过用户会员信息完成 apple 订单
     *
     * @param  MemberRecord  $memberRecord
     * @param  string        $evidence
     * @param  string        $password
     *
     * @throws GuzzleException
     * @throws \Exception
     */
    public function processAppleOrdersByMember(MemberRecord $memberRecord, string $evidence = '', string $password = '')
    {
        $tradePay = rep()->tradePay->getQuery()->where('id', $memberRecord->pay_id)->first();
        $user     = rep()->user->getQuery()->find($memberRecord->user_id);
        $payData  = rep()->payData->getQuery()->where('id', $tradePay->data_id)->first();

        if (!$evidence) {
            $requestParams = json_decode($payData->request_param, true);
            $evidence      = $requestParams['receipt-data'];
            $password      = $requestParams['password'];
        }

        $receiptResp = pocket()->tradePay->getAppleReceiptByEvidenceAndUrl($user, $evidence, $password,
            $payData->request_uri);
        if ($receiptResp->getStatus()) {
            $receipt = $receiptResp->getData();
            pocket()->tradePay->processAppleEvidence($user, $payData, $receipt['latest_receipt_info']);
        }
    }

    /**
     * 生成 ping++ 支付订单和应用内订单
     *
     * @param  User    $user
     * @param  Good    $good
     * @param  string  $clientId
     * @param  string  $clientIp
     * @param  string  $appName
     * @param  string  $os
     *
     * @return array
     */
    public function pingXxPay(User $user, Good $good, $appName, $os, $clientId, $clientIp, $entry, $reachEntry, $discountType)
    {
        [$charge, $payChannel] = $this->generatePingXxOrderByPay($user, $good, $os, $clientId, $clientIp);

        $tradePay = DB::transaction(function () use ($user, $good, $charge, $payChannel, $os) {
            $payData  = pocket()->payData->createPayDataByPingXxCharge($charge);
            $tradePay = $this->createTradePayByPingXx($payData, $user, $good, $charge,
                $payChannel, TradePay::OS_MAPPING[$os]);

            if ($good->use_discounts) {
                rep()->discount->getQuery()->whereIn('id', $good->use_discounts)
                    ->update(['pay_id' => $tradePay->id, 'status' => Discount::STATUS_DEFAULT]);
            }

            if ($good->abandoned_discounts) {
                rep()->discount->getQuery()->whereIn('id', $good->abandoned_discounts)
                    ->update(['pay_id' => $tradePay->id, 'status' => Discount::STATUS_ABANDONED]);
            }

            return $tradePay;
        });

        $channelCacheKey = config('redis_keys.cache.pay_channel_cache');
        $orderSceneKey   = sprintf(config('redis_keys.pingxx_order_scene'), $tradePay->id);
        $sceneData       = ['entry' => $entry, 'reach_entry' => $reachEntry, 'client_os' => $os, 'discount_type' => $discountType];
        if (!Redis::exists($channelCacheKey)) {
            $lock = new RedisLock(Redis::connection(), 'lock:' . $channelCacheKey, 3);
            $lock->block(3, function () use ($channelCacheKey, $os) {
                if (!Redis::exists($channelCacheKey)) {
                    $tomorrowStartAt = strtotime('+1 days', strtotime(date('Y-m-d')));
                    Redis::hIncrBy($channelCacheKey, $os, 1);
                    Redis::expire($channelCacheKey, $tomorrowStartAt - time());
                }
            });
        } else {
            Redis::hIncrBy($channelCacheKey, $os, 1);
        }

        if (!Redis::exists($orderSceneKey)) {
            $lock = new RedisLock(Redis::connection(), 'lock:' . $orderSceneKey, 3);
            $lock->block(3, function () use ($orderSceneKey, $sceneData) {
                if (!Redis::exists($orderSceneKey)) {
                    redis()->client()->set($orderSceneKey, json_encode($sceneData));
                    redis()->client()->expire($orderSceneKey, 3600);
                }
            });
        }

        return [$tradePay, $charge];
    }

    /**
     * 交易完成的通知
     *
     * @param  int  $userId
     * @param  int  $goodId
     *
     * @throws GuzzleException
     */
    public function tradePayDoneNotice(int $userId, int $goodId)
    {

        $good = rep()->good->getQuery()->select('product_id', 'goods.type', 'related_type',
            'price', 'is_default', 'goods.uuid', 'related_id')->find($goodId);
        if (in_array($good->getRawOriginal('type'), [Good::TYPE_WX_WAP, Good::TYPE_ALIPAY_WAP])) {
            $user = rep()->user->getQuery()->find($userId);
            $good = rep()->good->buildInfoAndPayByGood($good);

            $sender = pocket()->common->getSystemHelperByAppName('common');
            $body   = ['type' => NeteaseCustomCode::TRADE_PAY_DONE, 'data' => ['goods' => $good]];

            pocket()->netease->msgSendCustomMsg($sender, $user->uuid, $body,
                ['option' => ['push' => false, 'badge' => false]]);
        }
    }
}

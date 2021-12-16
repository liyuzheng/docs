<?php


namespace App\Http\Controllers;


use App\Constant\ApiBusinessCode;
use App\Http\Requests\Trades\ApplePayRequest;
use App\Http\Requests\Trades\GoodsAndMemberRequest;
use App\Http\Requests\Trades\GooglePayRequest;
use App\Http\Requests\Trades\OnlyNeedGoodIdRequest;
use App\Http\Requests\Trades\WebPingXxPayRequest;
use App\Models\Discount;
use App\Models\Good;
use App\Models\Task;
use App\Models\TradePay;
use App\Models\User;
use App\Models\Version;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    /**
     * 根据类型获取所有商品
     *
     * @param  GoodsAndMemberRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function goods(GoodsAndMemberRequest $request)
    {
        $clientOs    = user_agent()->os;
        $appName     = user_agent()->appName;
        $relatedType = Good::GOODS_TYPE_MAPPING[$request->query('type', Good::RELATED_TYPE_STR_CURRENCY)];
        $goodsOs     = Good::CLIENT_OS_MAPPING[$clientOs] ?? Good::CLIENT_OS_ANDROID;
        $platform    = $request->has('platform') ? Good::PLATFORM_MAPPING[$request->platform]
            : Good::OS_DEFAULT_PLATFORM_MAPPING[$goodsOs];

        $user = $request->user();
        if (!$user && $request->has('uuid')) {
            $user = rep()->user->getQuery()->where('uuid', $request->uuid)->first();
        } elseif ($user) {
            $user = rep()->user->getQuery()->where('id', $user->id)->first();
        }

        $goods = pocket()->good->getGoodsByCache($user, $goodsOs, $appName, $platform, $relatedType);
        if ($goodsOs == Good::CLIENT_OS_WEB || version_compare(user_agent()->clientVersion, '2.2.0', '>=')) {
            foreach ($goods as $index => $good) {
                unset($good['ori_price']);
                $good['is_default'] && $good['bottom_tips'] = trans('messages.most_purchases');
//                $good['info']['extra']['time_limit'] = trans($good['info']['extra']['time_limit']);
//                $good['info']['name']                = trans($good['info']['name']);
                $goods[$index]                       = $good;
            }
        }
        $goods = pocket()->good->judgeAndSetDiscount($user, $goods, $clientOs, $relatedType);


        return api_rr()->getOK($goods);
    }

    /**
     * 充值记录
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function records(Request $request)
    {
        $trades = rep()->tradePay->getQuery()->select('trade_pay.amount', 'goods.type',
            'trade_pay.related_type', 'trade_pay.related_id', 'trade_pay.created_at')
            ->join('goods', 'goods.id', 'trade_pay.good_id')->where('user_id', $request->user()->id)
            ->where('trade_pay.done_at', '>', 0)->orderBy('trade_pay.id', 'desc')
            ->paginate(20);

        if ($trades->getCollection()->count()) {
            $currencyIds = $trades->getCollection()->where('related_type',
                TradePay::RELATED_TYPE_RECHARGE)->pluck('related_id')->toArray();
            $currencies  = rep()->currency->getQuery()->withTrashed()->whereIn('id',
                $currencyIds)->get();
            foreach ($trades as $trade) {
                if ($trade->getRawOriginal('related_type') ==
                    TradePay::RELATED_TYPE_RECHARGE) {
                    $currency = $currencies->find($trade->getRawOriginal('related_id'));
                    $trade->setAttribute('related_type_tips', sprintf(
                        trans('messages.diamond_purchase_records_tmpl'),
                        $currency->getRawOriginal('amount') / 10));
                } else {
                    $trade->setAttribute('related_type_tips', trans('messages.purchase_member'));
                }

                $trade->setAttribute('ori_amount', $trade->getRawOriginal('amount') / 100);
                $trade->setAttribute('unit', trans('messages.currency_unit'));
            }
        }

        $nextPage = $trades->currentPage() + 1;

        return api_rr()->getOK(pocket()->util->getPaginateFinalData(
            $trades->getCollection(), $nextPage));
    }

    /**
     * 获取代币支付的商品列表
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function proxyCurrencyGoods(Request $request)
    {
        $goods = pocket()->good->getProxyCurrencyGoodsByCache(Good::RELATED_TYPE_CARD);

        return api_rr()->getOK($goods);
    }

    /**
     * 代币支付
     *
     * @param  OnlyNeedGoodIdRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function proxyCurrencyPay(OnlyNeedGoodIdRequest $request)
    {
        $user = $request->user();
        $good = rep()->good->getQuery()->where('uuid', (string)$request->good_id)->first();
        if ($good->getRawOriginal('type') != Good::TYPE_CURRENCY
            || $good->getRawOriginal('related_type') != Good::RELATED_TYPE_CARD) {
            return api_rr()->forbidCommon('Does not support the request');
        }

        $buyResp = pocket()->tradeBalance->buyMemberByProxyCurrency($user, $good);
        if (!$buyResp->getStatus()) {
            return api_rr()->customFailed($buyResp->getMessage(), $buyResp->getData());
        } else {
            pocket()->esUser->updateUserFieldToEs($user->id, ['is_member' => 1]);
        }

        return api_rr()->postOK((object)[]);
    }

    /**
     * 苹果支付
     *
     * @param  ApplePayRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function applePay(ApplePayRequest $request)
    {
        $user      = $request->user();
        $version   = rep()->version->getRecordByOsAndVersionAndAppName(
            Version::OS_MAPPING[user_agent()->os], user_agent()->clientVersion,
            user_agent()->appName);
        $isSandBox = !$version || !$version->audited_at;
        //        if (app()->environment('production') && $isSandBox && !in_array($user->id,
        //                config('custom.allow_sandbox_users'))) {
        //            return api_rr()->goodsFailure('内测包尚不支持购买，请前往AppStore下载正式包继续购买。');
        //        }

        $password    = $request->has('password') ? $request->password
            : config('custom.pay.apple.password');
        $receiptResp = pocket()->tradePay->getAppleReceiptByEvidence($user,
            $request->evidence, $password, $isSandBox);

        if ($receiptResp->getStatus()) {
            $receipt   = $receiptResp->getData();
            $payData   = pocket()->payData->createPayDataByAppleOrder($request->evidence,
                $password, $receipt, $isSandBox);
            $orderResp = pocket()->tradePay->getAppleSingleOrderByTradeNo($request->trade_no,
                $receipt['receipt']['in_app']);
            if (!$orderResp->getStatus()) {
                return api_rr()->customFailed($orderResp->getMessage(),
                    ApiBusinessCode::GOODS_FAILURE);
            }

            $order   = $orderResp->getData();
            $good    = rep()->good->getQuery()->where('uuid', $request->good_id)->first();
            $payResp = pocket()->tradePay->processAppleSingleOrder(
                $user, $payData, $good, $order);
            if ($payResp->getStatus()) {
                $type = Good::GOODS_TYPE_MAPPING[$good->getRawOriginal('related_type')];
                return api_rr()->postOK(['type' => $type]);
            }
        }

        return api_rr()->postOK((object)[]);
    }

    /**
     * ping++ 支付
     *
     * @param  OnlyNeedGoodIdRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pingXxPay(OnlyNeedGoodIdRequest $request)
    {
        $user         = rep()->user->getQuery()->find($request->user()->id);
        $gioData      = $request->post('gio_data');
        $entry        = $gioData && key_exists('entry', $gioData) ? $gioData['entry'] : '';
        $reachEntry   = $gioData && key_exists('reach_entry', $gioData) ? $gioData['reach_entry'] : '';
        $discountType = $gioData && key_exists('discount_type', $gioData) ? $gioData['discount_type'] : '';
        if ($user->getRawOriginal('gender') == User::GENDER_WOMEN
            && !pocket()->user->hasRole($user, User::ROLE_CHARM_GIRL)) {
            return api_rr()->forbidCommon('魅力女生才可以进行充值，请先前往认证');
        }

        $clientIp = get_client_real_ip();
        $clientId = $this->getClientId();
        $appName  = user_agent()->appName;
        $clientOs = user_agent()->os;
        $good     = rep()->good->getQuery()->where('uuid', $request->good_id)->first();

        $good = pocket()->good->judgeAndSetDiscount($user, $good, $clientOs,
            $good->getRawOriginal('related_type'));

        [$tradePay, $charge] = pocket()->tradePay->pingXxPay($user, $good, $appName,
            $clientOs, $clientId, $clientIp, $entry, $reachEntry, $discountType);

        return api_rr()->postOK([
            'order_no'    => $tradePay->order_no,
            'charge_data' => json_encode($charge),
        ]);
    }

    /**
     * ping++ web支付
     *
     * @param  WebPingXxPayRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function webPingXxPay(WebPingXxPayRequest $request)
    {
        $user         = rep()->user->getQuery()->where('uuid', $request->user_id)->first();
        $gioData      = $request->post('gio_data');
        $entry        = $gioData && key_exists('entry', $gioData) ? $gioData['entry'] : '';
        $reachEntry   = $gioData && key_exists('reach_entry', $gioData) ? $gioData['reach_entry'] : '';
        $discountType = $gioData && key_exists('discount_type', $gioData) ? $gioData['discount_type'] : '';
        if ($user->getRawOriginal('gender') == User::GENDER_WOMEN
            && !pocket()->user->hasRole($user, User::ROLE_CHARM_GIRL)) {
            return api_rr()->forbidCommon('魅力女生才可以进行充值，请先前往认证');
        }

        $clientIp = get_client_real_ip();
        $clientId = $this->getClientId();
        $appName  = user_agent()->appName;
        $clientOs = user_agent()->os;
        $good     = rep()->good->getQuery()->where('uuid', $request->good_id)->first();
        $good     = pocket()->good->judgeAndSetDiscount($user, $good, $clientOs,
            $good->getRawOriginal('related_type'));

        [$tradePay, $charge] = pocket()->tradePay->pingXxPay($user, $good, $appName,
            $clientOs, $clientId, $clientIp, $entry, $reachEntry, $discountType);

        return api_rr()->postOK([
            'order_no'    => $tradePay->order_no,
            'charge_data' => json_encode($charge),
        ]);
    }

    /**
     * Google 支付
     *
     * @param  GooglePayRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Google\Exception
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function googlePay(GooglePayRequest $request)
    {
        $productResp = pocket()->tradePay->getProductByGoogleOrder($request->package_name,
            $request->product_id, $request->token);
        /** @var \Google_Service_AndroidPublisher_ProductPurchase $product */
        $product = $productResp->getData();
        if (!$productResp->getStatus() || $product->purchaseState) {
            return api_rr()->notFoundResult('获取不到Google订单或订单支付状态不正确');
        }

        $user    = $request->user();
        $good    = rep()->good->getQuery()->where('product_id', $request->product_id)->first();
        $payData = pocket()->payData->createPayDataByGoogleOrder($request->package_name,
            $request->product_id, $request->token, $product);

        $tradePayResp = pocket()->tradePay->processGoogleOrder($user, $good,
            $payData, $product);

        if (!$tradePayResp->getStatus()) {
            return api_rr()->forbidCommon($tradePayResp->getMessage());
        }

        return api_rr()->postOK(['order_id' => $product->orderId]);
    }

    /**
     * 查看订单状态
     *
     * @param  string  $order
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tradeOrderStatus(string $order)
    {
        $tradePay = rep()->tradePay->getQuery()->where('order_no', $order)->first();
        $status   = $tradePay ? $tradePay->status : TradePay::STATUS_DEFAULT;

        return api_rr()->getOK(['status' => $status]);
    }

    /**
     * 支付页面前置接口
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tradePre(Request $request)
    {
        $user             = $request->user();
        $inviteTestRecord = rep()->userAb->getUserInviteTestRecord($user);
        $discount         = rep()->discount->getQuery()->where('user_id', $user->id)
            ->where('related_type', Discount::RELATED_TYPE_GIVING)
            ->where('expired_at', '>', time())->where('done_at', 0)->first();

        $response = ['type' => 'common'];
        if ($discount) {
            $inviteTestIsB    = $inviteTestRecord && $inviteTestRecord->inviteTestIsB();
            $response['type'] = $inviteTestIsB ? 'discount_invite' : 'member_invite';
            if ($inviteTestIsB) {
                $inviteCount   = rep()->task->getQuery()->where('user_id', $user->id)
                    ->where('related_type', Task::RELATED_TYPE_MEMBER_DISCOUNT)
                    ->where('status', Task::STATUS_DEFAULT)->count();
                $ownedDiscount = rep()->discount->getQuery()->where('user_id', $user->id)
                    ->where('type', Discount::TYPE_CAN_OVERLAP)->where('done_at', 0)
                    ->where('related_type', Discount::RELATED_TYPE_INVITE_PRIZE)
                    ->sum('discount');
                $invite        = [
                    'invite_count'   => $inviteCount,
                    'discount_max'   => 50,
                    'discount_min'   => 5,
                    'owned_discount' => (int)sprintf('%.0f', ($ownedDiscount > 0.5 ? 0.5
                            : $ownedDiscount) * 100)
                ];

                $response['invite'] = $invite;
            }

            $discountData         = [
                'expired_at_ts' => $discount->getRawOriginal('expired_at'),
                'image'         => cdn_url('uploads/common/' . ($inviteTestIsB ?
                        'discount_invite30.png' : 'member_invite30.png'))
            ];
            $response['discount'] = $discountData;
        }

        return api_rr()->getOK($response);
    }

    /**
     * 折扣
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function discount(Request $request, int $uuid)
    {
        $clientOs           = user_agent()->os;
        $user               = rep()->user->getQuery()->where('uuid', $uuid)->first();
        $overlapDiscounts   = rep()->discount->getOverlapDiscounts($user);
        $overlapDiscountSum = $overlapDiscounts->sum('discount');

        if ($overlapDiscountSum < 0.5) {
            $discount = rep()->discount->getNotOverlapMinDiscount($user, $clientOs);
            if ($discount->discount < 1) {
                switch ($discount->related_type) {
                    case Discount::RELATED_TYPE_TENCENT:
                        $tips = sprintf('公众号充值立减%.0f%%', (1 - $discount->discount) * 100);
                        break;
                    case Discount::RELATED_TYPE_RENEWAL:
                        $tips = sprintf('续费VIP限时立减%.0f%%', (1 - $discount->discount) * 100);
                        break;
                    case Discount::RELATED_TYPE_INVITE:
                        $tips = sprintf('被邀请用户首充立减%.0f%%', (1 - $discount->discount) * 100);
                        break;
                    default:
                        $template = $discount->expired_at ? trans('messages.time_limit_discount_tmpl')
                            : trans('messages.discount_tmpl');
                        $tips     = sprintf($template, (1 - $discount->discount) * 100);
                }

                if ($overlapDiscountSum) {
                    $lackOfDiscount  = 0.5 - (1 - $discount->discount);
                    $overlapDiscount = $overlapDiscountSum > $lackOfDiscount ? $lackOfDiscount
                        : $overlapDiscountSum;
                    $usedDiscount    = $discount->discount - $overlapDiscount;
                    $tips            = sprintf(trans('messages.invite_discount_tmpl') . '+',
                            $overlapDiscount * 100) . $tips . sprintf('=立减%.0f%%', (1 - $usedDiscount) * 100);
                    $discount->setAttribute('discount', $usedDiscount);
                }
            } else {
                $tips = $overlapDiscountSum ? sprintf(trans('messages.invite_discount_tmpl'),
                    $overlapDiscountSum * 100) : '获取会员立享特权';
            }

            $responseData = [
                'discount' => $discount->discount < 1 ? $discount->discount : $overlapDiscountSum,
                'tips'     => [['color' => '#FF9B06', 'txt' => $tips]]
            ];
        } else {
            $responseData = [
                'discount' => 0.5,
                'tips'     => [
                    ['color' => '#FF9B06', 'txt' => sprintf(trans('messages.invite_discount_tmpl'), 50)]
                ]
            ];
        }

        !isset($responseData) && $responseData = (object)[];

        return api_rr()->getOK($responseData);
    }
}

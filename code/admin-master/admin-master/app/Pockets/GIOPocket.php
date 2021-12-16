<?php


namespace App\Pockets;

use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Handlers\Gio\GrowingIO;
use App\Models\Good;
use App\Models\TradePay;
use App\Models\User;
use App\Models\Card;
use App\Models\Discount;

class GIOPocket extends BasePocket
{
    const EVENT_REGISTER_SUCCESS         = 'registerSuccess';
    const EVENT_CHARM_AUTH_PASS          = 'glamourCertificationSuccess';
    const EVENT_DIAMOND_RECHARGE_SUCCESS = 'diamondRechargeSuccess';
    const EVENT_DIAMOND_REFUND_SUCCESS   = 'diamondRefundSuccess';
    const EVENT_PRIVATE_CHAT_SUCCESS     = 'efficientPrivateTalk';
    const EVENT_INVITE_USER_REGISTER     = 'inviteUserRegisterSuccess';
    const EVENT_COUPON_RECEIVE           = 'couponReceive';
    const EVENT_CHARM_DELETE             = 'glamourCertificationCancel';
    const EVENT_VIP_PAY_SUCCESS          = 'VIPPaySuccess';

    private $gioInstance;

    public function __construct()
    {
        $accountId = config('custom.gio_account');
        if (!isset($this->gioInstance)) {
            $this->gioInstance = GrowingIO::getInstance($accountId);
        }
    }

    public function report($userUUid, $eventKey, $data)
    {
        $this->gioInstance->track((string)$userUUid, $eventKey, $data);
    }

    public function reportUserPayMember($tradePay, $user)
    {
        $orderSceneKey = sprintf(config('redis_keys.pingxx_order_scene'), $tradePay->id);
        $orderScene    = redis()->client()->get($orderSceneKey);
        $orderSceneArr = json_decode($orderScene);
        $data          = [
            'VIPEntry_var'         => $orderSceneArr->entry ?? '无',
            'VIPType_var'          => '无',
            'VIPPayType_var'       => '无',
            'VIPOriginalPrice_var' => $tradePay->ori_amount / 100,
            'VIPPayPrice_var'      => $tradePay->amount / 100,
            'discountType_var'     => $orderSceneArr->discount_type ?? '无',
            'reachEntry_var'       => $orderSceneArr->reach_entry ?? '无',
            'rechargeSource_var'   => '无',
            'PayType_var'          => '无',
            'inviteUserID_var'     => '无',
            'InvitationCode_var'   => '无'
        ];
        if ($orderSceneArr->reach_entry == '普通弹框') {
            $hasPayMember = rep()->tradePay->m()
                ->where('user_id', $user->id)
                ->where('related_type', TradePay::RELATED_TYPE_RECHARGE_VIP)
                ->where('done_at', '>', 0)
                ->first();
            if ($hasPayMember) {
                $data['reachEntry_var'] = '复购弹框';
            }
        }
        $card                   = rep()->card->getById($tradePay->related_id);
        $data['VIPType_var']    = $card->name;
        $memberRecordCount      = rep()->memberRecord->m()->where('user_id')->count();
        $data['VIPPayType_var'] = $memberRecordCount > 1 ? '续费' : '开通';
        //        $discount                   = rep()->discount->m()->where('pay_id', $tradePay->id)->first();
        $data['rechargeSource_var'] = ($orderSceneArr->client_os == 'web') ? '公众号' : 'APP';
        $good                       = rep()->good->getById($tradePay->good_id);
        switch ($good->type) {
            case Good::TYPE_STR_ALIPAY:
            case Good::TYPE_STR_ALIPAY_WAP:
                $data['PayType_var'] = '支付宝';
                break;
            case Good::TYPE_STR_WECHAT:
            case Good::TYPE_STR_WX_WAP:
                $data['PayType_var'] = '微信';
                break;
        }
        $inviteRecord = rep()->inviteRecord->m()->where('target_user_id', $user->id)->first();
        if ($inviteRecord) {
            $inviteUser                 = rep()->user->getById($inviteRecord->user_id);
            $inviteUserDetail           = rep()->userDetail->getByUserId($inviteRecord->user_id);
            $data['inviteUserID_var']   = $inviteUser->uuid;
            $data['InvitationCode_var'] = $inviteUserDetail->invite_code;
        }
        pocket()->gio->report($user->uuid, GIOPocket::EVENT_VIP_PAY_SUCCESS, $data);
        $this->reportMemberNoRealDiscount($orderSceneArr->client_os, $user, $tradePay);
        $this->reportMemberDiscount($tradePay, $user);
        redis()->client()->del($orderSceneKey);
    }

    public function reportUserPayCurrency($tradePay, $user)
    {
        $data = [
            'paymentType_var'  => '',
            'paymentMoney_var' => $tradePay->amount / 100,
            'PayType_var'      => '',
        ];
        $good = rep()->good->getById($tradePay->good_id);
        switch ($good->type) {
            case Good::TYPE_STR_ALIPAY:
            case Good::TYPE_STR_ALIPAY_WAP:
                $data['PayType_var'] = '支付宝';
                break;
            case Good::TYPE_STR_WECHAT:
            case Good::TYPE_STR_WX_WAP:
                $data['PayType_var'] = '微信';
                break;
        }
        $currency                = rep()->currency->getById($tradePay->related_id);
        $data['paymentType_var'] = ($currency->amount / 10) . '元';
        pocket()->gio->report($user->uuid, GIOPocket::EVENT_DIAMOND_RECHARGE_SUCCESS, $data);
        $orderSceneKey = sprintf(config('redis_keys.pingxx_order_scene'), $tradePay->id);
        redis()->client()->del($orderSceneKey);
    }

    /**
     * 上报有实体优惠券的会员充值
     *
     * @param $tradePay
     * @param $user
     */
    private function reportMemberDiscount($tradePay, $user)
    {
        $discount   = rep()->discount->m()->where('pay_id', $tradePay->id)->get();
        $couponData = [
            'not_overlap' => [],
            'can_overlap' => []
        ];
        foreach ($discount as $item) {
            if ($item->type == Discount::TYPE_NOT_OVERLAP) {
                if ($item->discount == 0.7) {
                    $couponData['not_overlap'] = [
                        'couponName_var'   => '活跃第三天送限时立减30%',
                        'inviteNumber_var' => 0,
                        'method_var'       => '系统发放',
                    ];

                }
                if ($item->discount == 0.8) {
                    $couponData['not_overlap'] = [
                        'couponName_var'   => '魅力女生邀请来的用户首次充值vip立减20%',
                        'inviteNumber_var' => 0,
                        'method_var'       => '系统发放',
                    ];

                }
            } elseif ($item->type == Discount::TYPE_CAN_OVERLAP) {
                $couponData['can_overlap'] = [
                    'couponName_var'   => 'B类用户每邀请一个用户立减5%',
                    'inviteNumber_var' => 0,
                    'method_var'       => '系统发放',
                ];
            }
        }
        foreach ($couponData as $key => $value) {
            if (count($value) > 0) {
                pocket()->gio->report($user->uuid, GIOPocket::EVENT_COUPON_RECEIVE, $couponData[$key]);
            }
        }
    }

    /**
     * 上报没有实体优惠券的会员充值
     *
     * @param $os
     * @param $user
     * @param $tradePay
     */
    private function reportMemberNoRealDiscount($os, $user, $tradePay)
    {
        if ($os == 'web') {
            $couponData = [
                'couponName_var'   => '公众号充值立减5%',
                'inviteNumber_var' => 0,
                'method_var'       => '系统发放',
            ];
            pocket()->gio->report($user->uuid, GIOPocket::EVENT_COUPON_RECEIVE, $couponData);
        }
        $member       = rep()->member->getQuery()->select('member.user_id', 'member.start_at', 'member.duration',
            'card.level')->join('card', 'card.id', 'member.card_id')
            ->where('member.user_id', $user->id)->first();
        $memberRecord = rep()->memberRecord->m()
            ->where('user_id', $user->id)
            ->where('pay_id', '<', $tradePay->id)
            ->orderByDesc('id')
            ->first();
        $expireAt     = $memberRecord ? $memberRecord->expired_at : 0;
        if ($expireAt > time() && $user->gender != User::GENDER_WOMEN
            && $member->getRawOriginal('level') != Card::LEVEL_FREE_VIP) {
            if ($expireAt < time() + Good::DISCOUNT_MINIMUM_SECONDS) {
                $couponData = [
                    'couponName_var'   => '最后三天送立减20%',
                    'inviteNumber_var' => 0,
                    'method_var'       => '系统发放',
                ];
                pocket()->gio->report($user->uuid, GIOPocket::EVENT_COUPON_RECEIVE, $couponData);
            }
        }
    }
}

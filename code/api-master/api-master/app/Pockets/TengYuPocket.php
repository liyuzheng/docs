<?php


namespace App\Pockets;

use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Foundation\Services\Guzzle\GuzzleHandle;

class TengYuPocket extends BasePocket
{
    private static $guzzleInstance;

    public static function getGuzzleInstance()
    {
        if (!self::$guzzleInstance instanceof \GuzzleHttp\Client) {
            $guzzleHandle         = new GuzzleHandle();
            self::$guzzleInstance = $guzzleHandle->getClient();
        }

        return self::$guzzleInstance;
    }

    /**
     * 初始化header
     *
     * @return ResultReturn
     */
    private function initJHeader()
    {
        $headers = [
            'Content-Type' => 'application/json;charset=utf-8',
            'Accept'       => 'application/json'
        ];

        return ResultReturn::success($headers);
    }

    /**
     * 发送请求
     *
     * @param         $method
     * @param         $api
     * @param  array  $body
     * @param         $area
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function jRequest($method, $api, array $body, $area)
    {
        switch ($area) {
            case 'beijing':
            case 'other':
                $userName = config('sms.tengYu.bjUserName');
                $password = config('sms.tengYu.bjPassword');
                break;
            default:
                return ResultReturn::failed('未找到配置');
        }
        $body['userName'] = $userName;
        $body['password'] = $password;
        try {
            $client   = self::getGuzzleInstance();
            $response = $client->request($method, $api, [
                'headers' => $this->initJHeader()->getData(),
                'body'    => json_encode($body),
                'timeout' => 3
            ]);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }
        $decodeResponse = json_decode($response->getBody()->getContents(), true);

        return ResultReturn::success($decodeResponse);
    }

    /**
     * 非北京地区发送请求
     *
     * @param         $method
     * @param         $api
     * @param  array  $body
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function otherRequest($method, $api, array $body)
    {
        $body['userName'] = config('sms.tengYu.otherUserName');
        $body['password'] = config('sms.tengYu.otherPassword');
        try {
            $client   = self::getGuzzleInstance();
            $response = $client->request($method, $api, [
                'headers' => $this->initJHeader()->getData(),
                'body'    => json_encode($body),
                'timeout' => 3
            ]);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }
        $decodeResponse = json_decode($response->getBody()->getContents(), true);

        return ResultReturn::success($decodeResponse);
    }

    /**
     * 发送短信消息
     *
     * @param $user
     * @param $group
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendXiaoquanUserContent($user, $group)
    {
        $api     = config('sms.tengYu.batchSendMessage.api');
        $method  = config('sms.tengYu.batchSendMessage.method');
        $msgHead = substr($user->mobile, -4);
        $isHide  = rep()->user->isHideUser($user->id);
        if ($isHide) {
            return ResultReturn::success(['hide' => true]);
        }

        switch ($group) {
            case 'charm_pass':
                //                $otherMsg   = "【小圈】{$msgHead}您的魅力女神认证已经通过审核，可以打开App使用啦~ 注意：请勿主动散播个人联系方式，平台将会对违反者做出封号惩罚。退订回N";
                $otherMsg   = sprintf(trans('messages.review_pass_sms_tmpl', [], $user->language), $msgHead);
                $beijingMsg = sprintf(trans('messages.review_pass_sms_tmpl', [], $user->language), $msgHead);
                break;
            case 'charm_fail':
                $otherMsg   = sprintf(trans('messages.review_not_pass_sms_tmpl', [], $user->language), $msgHead);
                $beijingMsg = sprintf(trans('messages.review_not_pass_sms_tmpl', [], $user->language), $msgHead);
                break;
            case 'active_charm':
                $otherMsg   = sprintf(trans('messages.unread_sms_tmpl', [], $user->language), $msgHead);
                $beijingMsg = sprintf(trans('messages.unread_sms_tmpl', [], $user->language), $msgHead);
                break;
            default:
                return ResultReturn::failed('未找到对应文案');
        }
        $mobile     = pocket()->user->getUserMobileAttribution($user->mobile);
        $mobileData = $mobile->getData();
        if ($mobile->getStatus() == false || $mobileData['data'] == null) {
            return ResultReturn::failed($mobile->getMessage());
        }
        $city = $mobileData['data']['city'];
        if ($city == '北京') {
            $area    = 'beijing';
            $content = $beijingMsg;
        } else {
            $area    = 'other';
            $content = $otherMsg;
        }
        $result = $this->jRequest($method, $api, [
            'messageList' => [
                [
                    'phone'   => $user->mobile,
                    'content' => $content
                ]
            ]
        ], $area);
        if ($result->getStatus() == false) {
            return ResultReturn::failed($result->getMessage());
        }

        return ResultReturn::success($result->getData());
    }

    /**
     * 给没领取奖励的用户发送短信
     *
     * @param $user
     * @param $day
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendUserGiftContent($user, $day)
    {
        $api    = config('sms.tengYu.batchSendMessage.api');
        $method = config('sms.tengYu.batchSendMessage.method');
        $isHide = rep()->user->isHideUser($user->id);
        if ($isHide) {
            return ResultReturn::success(['hide' => true]);
        }
        $content = sprintf(trans('messages.invite_reward_not_receive_sms_tmpl', [],
            $user->language), $user->nickname, $day);
        $result  = $this->otherRequest($method, $api, [
            'messageList' => [['phone' => $user->mobile, 'content' => $content]]
        ]);
        if ($result->getStatus() == false) {
            return ResultReturn::failed($result->getMessage());
        }

        return ResultReturn::success($result->getData());
    }

    /**
     * 打折短信
     *
     * @param $user
     * @param $residualDays
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendDiscountMessage($user, $residualDays)
    {
        $api    = config('sms.tengYu.batchSendMessage.api');
        $method = config('sms.tengYu.batchSendMessage.method');
        $isHide = rep()->user->isHideUser($user->id);
        if ($isHide) {
            return ResultReturn::success(['hide' => true]);
        }
        $content = sprintf(trans('messages.vip_remaining_sms_tmpl', [],
            $user->language), $residualDays);
        $result  = $this->otherRequest($method, $api, [
            'messageList' => [['phone' => $user->mobile, 'content' => $content]]
        ]);
        if ($result->getStatus() == false) {
            return ResultReturn::failed($result->getMessage());
        }

        return ResultReturn::success($result->getData());
    }

    /**
     * 发送七日未活跃女生短信
     *
     * @param $user
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendCharmActiveRemindMessage($user)
    {
        $api    = config('sms.tengYu.batchSendMessage.api');
        $method = config('sms.tengYu.batchSendMessage.method');
        $isHide = rep()->user->isHideUser($user->id);
        if ($isHide) {
            return ResultReturn::success(['hide' => true]);
        }
        $message = sprintf(trans('messages.charm_not_active_sms', [], 'zh'));
        $result  = $this->otherRequest($method, $api, [
            'messageList' => [['phone' => $user->mobile, 'content' => $message]]
        ]);

        return ResultReturn::success($result->getData());
    }

    /**
     * 发送七天未活跃短信
     *
     * @param $user
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendSevenDaysActiveMessage($user)
    {
        $api    = config('sms.tengYu.batchSendMessage.api');
        $method = config('sms.tengYu.batchSendMessage.method');
        $isHide = rep()->user->isHideUser($user->id);
        if ($isHide) {
            return ResultReturn::success(['hide' => true]);
        }

        $message = trans('messages.charm_not_active_sms1', [], 'zh');
        $result  = $this->otherRequest($method, $api, [
            'messageList' => [['phone' => $user->mobile, 'content' => $message]]
        ]);

        return ResultReturn::success($result->getData());
    }

    /**
     * 发送后台锁微信短信
     *
     * @param $user
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendAdminLockWechatMessage($user)
    {
        $api    = config('sms.tengYu.batchSendMessage.api');
        $method = config('sms.tengYu.batchSendMessage.method');
        $isHide = rep()->user->isHideUser($user->id);
        if ($isHide) {
            return ResultReturn::success(['hide' => true]);
        }

        $message = trans('messages.wechat_invalid_sms', [], $user->language);
        $result  = $this->otherRequest($method, $api, [
            'messageList' => [['phone' => $user->mobile, 'content' => $message]]
        ]);

        return ResultReturn::success($result->getData());
    }

    /**
     * 发送拉黑解除短信
     *
     * @param $user
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendUnlockBlackMessage($user)
    {
        $api    = config('sms.tengYu.batchSendMessage.api');
        $method = config('sms.tengYu.batchSendMessage.method');
        $isHide = rep()->user->isHideUser($user->id);
        if ($isHide) {
            return ResultReturn::success(['hide' => true]);
        }
        $message = trans('messages.unblock_sms', [], $user->language);
        $result  = $this->otherRequest($method, $api, [
            'messageList' => [['phone' => $user->mobile, 'content' => $message]]
        ]);

        return ResultReturn::success($result->getData());
    }

    /**
     * 给不活跃用户发送短信
     *
     * @param $mobiles
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendActiveRemindMessage($mobiles)
    {
        $api     = config('sms.tengYu.sendMessage.api');
        $method  = config('sms.tengYu.sendMessage.method');
        $message = '【小圈】没上线的这段时间，新入上百位同城女生、有女生多次查看你的主页，点击查看https://dwz.cn/JxkuauVE 回TD退订';
        $result  = $this->otherRequest($method, $api, [
                'content'   => $message,
                'phoneList' => $mobiles
            ]
        );

        return ResultReturn::success($result->getData());
    }

    /**
     * 给ios掉包造成影响的用户发送短信[ios_down_call_20210711]
     *
     * @param $mobiles
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendIosDownCallSmsMessage($mobiles)
    {
        $api     = config('sms.tengYu.sendMessage.api');
        $method  = config('sms.tengYu.sendMessage.method');
        $message = '【小圈】iOS苹果最新下载地址请前往微信公众号【小圈app】获取 ~ 退订回N';
        $result  = $this->otherRequest($method, $api, [
                'content'   => $message,
                'phoneList' => $mobiles
            ]
        );

        return ResultReturn::success($result->getData());
    }

    /**
     * 给异常的用户连续发7天消息
     *
     * @param $mobiles
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendErrorUserMsg($mobiles)
    {
        $api     = config('sms.tengYu.sendMessage.api');
        $method  = config('sms.tengYu.sendMessage.method');
        $message = '【小圈】由于网络异常导致部分用户无法正常使用，请删除当前小圈App后前往微信公众号[小圈App]或点击链接下载最新版。（请务必删除当前小圈App后再下载）给您带来不便深表歉意 https://dwz.cn/JxkuauVE 退订回N';
        $result  = $this->otherRequest($method, $api, [
                'content'   => $message,
                'phoneList' => $mobiles
            ]
        );

        return ResultReturn::success($result->getData());
    }
}

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
        switch ($group) {
            case 'charm_pass':
                $otherMsg   = "【小圈】{$msgHead}您的魅力女神认证已经通过审核，可以打开App使用啦~ 注意：请勿主动散播个人联系方式，平台将会对违反者做出封号惩罚。退订回N";
                $beijingMsg = "【小圈】{$msgHead}您的魅力女神认证已经通过审核，可以打开App使用啦~ 注意：请勿主动散播个人联系方式，平台将会对违反者做出封号惩罚。退订回N";
                break;
            case 'charm_fail':
                $otherMsg   = "【小圈】{$msgHead}您的魅力女神认证没有通过审核，打开App查看原因~ 退订回N";
                $beijingMsg = "【小圈】{$msgHead}您的魅力女神认证没有通过审核，打开App查看原因~ 退订回N";
                break;
            case 'active_charm':
                $otherMsg   = "【小圈】{$msgHead}附近有未读新消息，快打开小圈看看吧~ 退订回N";
                $beijingMsg = "【小圈】{$msgHead}附近有未读新消息，快打开小圈看看吧~ 退订回N";
                break;
            default:
                return ResultReturn::failed('未找到对应文案');
        }
        $content = $beijingMsg;
        $area    = 'other';
        $result  = $this->jRequest($method, $api, [
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
        $api     = config('sms.tengYu.batchSendMessage.api');
        $method  = config('sms.tengYu.batchSendMessage.method');
        $content = '【小圈】尊敬的' . $user->nickname . '：您还有邀请奖励会员' . $day . '天未领取，快来小圈App查看吧~ 退订回N';
        $result  = $this->otherRequest($method, $api, [
            'messageList' => [
                [
                    'phone'   => $user->mobile,
                    'content' => $content
                ]
            ]
        ]);
        if ($result->getStatus() == false) {
            return ResultReturn::failed($result->getMessage());
        }

        return ResultReturn::success($result->getData());
    }


    /**
     * 发送七天未活跃短信
     *
     * @param $mobile
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendSevenDaysActiveMessage($mobile)
    {
        $api     = config('sms.tengYu.batchSendMessage.api');
        $method  = config('sms.tengYu.batchSendMessage.method');
        $message = '【小圈】你已经有一周未打开小圈，系统降低了你的排序减少曝光，快上线恢复吧！退订回N';
        $result  = $this->otherRequest($method, $api, [
            'messageList' => [
                [
                    'phone'   => $mobile,
                    'content' => $message
                ]
            ]
        ]);

        return ResultReturn::success($result->getData());
    }

    /**
     * 发送后台锁微信短信
     *
     * @param $mobile
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendAdminLockWechatMessage($mobile)
    {
        $api     = config('sms.tengYu.batchSendMessage.api');
        $method  = config('sms.tengYu.batchSendMessage.method');
        $message = '【小圈】多人反馈你的微信搜索不到、添加未成功，管理员隐藏了你的微信，快打开小圈恢复吧！退订回N';
        $result  = $this->otherRequest($method, $api, [
            'messageList' => [
                [
                    'phone'   => $mobile,
                    'content' => $message
                ]
            ]
        ]);

        return ResultReturn::success($result->getData());
    }

    /**
     * 发送拉黑解除短信
     *
     * @param $mobile
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendUnlockBlackMessage($mobile)
    {
        $api     = config('sms.tengYu.batchSendMessage.api');
        $method  = config('sms.tengYu.batchSendMessage.method');
        $message = '【小圈】你已被解除拉黑，快打开小圈App查看吧。';
        $result  = $this->otherRequest($method, $api, [
            'messageList' => [
                [
                    'phone'   => $mobile,
                    'content' => $message
                ]
            ]
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
}

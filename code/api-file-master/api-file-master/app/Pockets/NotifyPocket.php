<?php


namespace App\Pockets;


use App\Constant\NeteaseCustomCode;
use App\Models\SwitchModel;
use App\Models\User;
use App\Models\UserAuth;
use App\Models\UserReview;
use App\Models\WechatTemplateMsg;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use GuzzleHttp\Exception\GuzzleException;
use Overtrue\EasySms\EasySms;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\PhoneNumber;

class NotifyPocket extends BasePocket
{
    /**
     * 发送绑定手机短信[阿里云]
     *
     * @param  string  $appName appName
     * @param  int     $area 手机号地区
     * @param  int     $code 手机验证码
     * @param  int     $mobile 手机号
     *
     * @return ResultReturn
     */
    public function sms(string $appName, $area, int $code, int $mobile)
    {
        $template  = 'SMS_212710328';
        $templates = ['SMS_202370270' => [86], 'SMS_212700381' => [852, 853]];
        foreach ($templates as $key => $areas) {
            if (in_array($area, $areas)) {
                $template = $key;
                continue;
            }
        }

        $sendMobile    = !in_array($area, [86]) ? new PhoneNumber($mobile, $area) : $mobile;
        $appNameConfig = ['common' => '小圈', 'mianju' => '小圈'];
        $signName      = isset($appNameConfig[$appName]) ? $appNameConfig[$appName]
            : $appNameConfig['common'];
        $config        = config('sms');

        $config['gateways']['aliyun']['sign_name'] = $signName;
        $easySms                                   = new EasySms($config);

        try {
            $response = $easySms->send($sendMobile,
                ['template' => $template, 'data' => ['code' => $code],], ['aliyun']);
        } catch (\Exception $exception) {
            $mongoData = ['mobile' => $mobile];
            if ($exception instanceof InvalidArgumentException) {
                $mongoData['error_msg'] = $exception->getMessage();
            } elseif ($exception instanceof NoGatewayAvailableException) {
                $mongoData['error']      = json_encode($exception->getExceptions());
                // fix 数据返回格式改变
                // dd($exception->getExceptions()['aliyun']->raw['Message']);
                $mongoData['error_msg']  = $exception->getExceptions()['aliyun']->raw['Message'];
                $mongoData['error_code'] = $exception->getExceptions()['aliyun']->raw['Code'];
            }
            logger()->setLogType('send_sms_failed')->error(json_encode($mongoData));
            mongodb('message_error')->insert($mongoData);
            return ResultReturn::failed(trans('messages.sms_send_abnormal'));
        }

        if ($response['aliyun']['status'] !== 'success') {
            return ResultReturn::failed(trans('messages.sms_flow_control'));
        }

        return ResultReturn::success(['mobile' => $mobile, 'code' => $code]);
    }


    /**
     * 发送绑定公众号成功自定义云信消息
     *
     * @param  int  $userId
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function weChatOfficeBindOk(int $userId)
    {
        $sender = config('custom.little_helper_uuid');
        $user   = rep()->user->getById($userId);
        $body   = [
            'type' => NeteaseCustomCode::FOLLOW_OS_STATE,
            'data' => [
                'is_follow' => true
            ]
        ];
        $resp   = pocket()->netease->sendMomentLikeCountMsg($sender, $user->uuid, $body);

        return ResultReturn::success($resp);
    }

    /**
     * 发送模板消息
     *
     * @param  int  $sendUserId
     * @param  int  $receiveUserId
     * @param       $msg
     * @param       $sendTime
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function sendToChatMsgToWeChatTemplate(int $sendUserId, int $receiveUserId, $msg, $sendTime)
    {
        $userAuth = rep()->userAuth->getQuery()
            ->where('user_id', $receiveUserId)
            ->where('type', UserAuth::TYPE_OFFICE_OPENID)
            ->first();
        if (!$userAuth) {
            return ResultReturn::failed('没有openid');
        }
        $switch = rep()->switchModel->getPushTemMsg();
        if ($switch) {
            $userSwitch = rep()->userSwitch->getUserSwitch($receiveUserId, $switch->id);
            //如果没有switch要添加
            if (!$userSwitch) {
                pocket()->userSwitch->postSyncPushTemMsgStateByFollow($receiveUserId, true);
            } else {
                if ($userSwitch->getOriginal('status') === SwitchModel::DEFAULT_STATUS_CLOSE) {
                    return ResultReturn::failed('没有开关或者开关关闭');
                }
            }
        }
        $users       = rep()->user->getByIds([$sendUserId, $receiveUserId]);
        $sendUser    = $users->where('id', $sendUserId)->first();
        $receiveUser = $users->where('id', $receiveUserId)->first();
        $openId      = $userAuth->secret;
        $templateId  = config('custom.wechat_template_msg_chat');
        $weChatApp   = pocket()->wechat->getWechatOfficeApp();
        $reqData     = [
            'touser'      => $openId,
            'template_id' => $templateId,
            //            'url'         => 'https://www.baidu.com',
            'data'        => [
                'first'    => $receiveUser->nickname . ' 收到了消息',
                'keyword1' => $sendUser->nickname,
                'keyword2' => ($sendUser->gender == 1) ? '男用户' : '女用户',
                'keyword3' => '私聊消息',
                'keyword4' => date('Y-m-d H:i:s', $sendTime),
                'remark'   => '',
            ],
        ];
        $createArr   = [
            'send_id'    => $sendUserId,
            'receive_id' => $receiveUserId,
            'msg'        => $msg,
            'req_data'   => json_encode($reqData),
            'send_at'    => $sendTime
        ];
        $dbMsg       = rep()->wechatTemplate->getQuery()->create($createArr);
        try {
            $resp = $weChatApp->template_message->send($reqData);
        } catch (\Exception $e) {
            $dbMsg->update(['status' => WechatTemplateMsg::STATUS_FAILED]);

            return ResultReturn::failed($e->getMessage());
        }
        $dbMsg->update(['resp_data' => json_encode($resp)]);
        if (isset($resp['errcode']) && $resp['errcode']) {
            $dbMsg->update(['status' => WechatTemplateMsg::STATUS_FAILED, 'error_code' => $resp['errcode']]);

            return ResultReturn::failed($resp['errmsg'], $resp);
        }
        $dbMsg->update(['status' => WechatTemplateMsg::STATUS_SUCCEED]);

        return ResultReturn::success($resp);
    }

    /**
     * 通过用户id获得用户应该发送的消息
     *
     * @param  int  $userId
     *
     * @return string
     */
    public function getUserSendSubscribeMsgType(int $userId)
    {
        $userReview = rep()->userReview->getLatestUserReview($userId);
        if (!$userReview) {
            return 'normal';
        }
        $user = rep()->user->getById($userId);
        if (($user->getOriginal('gender') === User::GENDER_WOMEN) &&
            in_array($userReview->getOriginal('check_status'),
                [UserReview::CHECK_STATUS_FOLLOW_WECHAT, UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE]
            )) {
            return 'charm_girl';
        }

        return 'normal';
    }

    /**
     * 根据消息类型发送消息
     *
     * @param  string  $type
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\BadRequestException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function sendWeChatSubscribeMsgByType(string $type)
    {
        switch ($type) {
            case 'normal':
                $response = pocket()->notify->weChatSubscribeNormalMsg();
                break;
            case 'charm_girl':
                $response = pocket()->notify->weChatSubscribeCharmGirlMsg();
                break;
            default:
                $response = pocket()->notify->weChatSubscribeNormalMsg();
                break;
        }

        return $response;
    }

    /**
     * 发送微信关注消息
     *
     * @param  int  $userId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\BadRequestException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function sendWeChatSubscribeMsgByUserId(int $userId)
    {
        $userReview = rep()->userReview->getLatestUserReview($userId);
        if (!$userReview) {
            return pocket()->notify->weChatSubscribeNormalMsg();
        }
        $user = rep()->user->getById($userId);
        if (($user->getOriginal('gender') === User::GENDER_WOMEN) &&
            in_array($userReview->getOriginal('check_status'),
                [UserReview::CHECK_STATUS_FOLLOW_WECHAT, UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE]
            )) {

            return pocket()->notify->weChatSubscribeCharmGirlMsg();
        }

        return pocket()->notify->weChatSubscribeNormalMsg();
    }

    /**
     * 推送普通用户关注消息
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\BadRequestException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function weChatSubscribeNormalMsg()
    {
        $app = pocket()->wechat->getWechatOfficeApp();

        $app->server->push(function ($message) {
            $message = <<<EOF
好开心，终于等到你🎉

送你会员充值九折福利！<a href="https://web-pay.wqdhz.com/wechat/payment">点击链接</a> 立即充值

也可以在这里获得小圈App最新版下载链接及相关资讯

认准官网唯一下载渠道：小圈App公众号，从此下载不再迷路 <a href="https://web.wqdhz.com/download4">点击下载</a>
EOF;

            return $message;
        });

        return $app->server->serve();
    }

    /**
     * 推送普通用户关注消息
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\BadRequestException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function weChatSubscribeCharmGirlMsg()
    {
        $app = pocket()->wechat->getWechatOfficeApp();

        $app->server->push(function ($message) {
            return '关注成功！请回到小圈点击“验证”按钮，即可完成魅力女生审核~';
        });

        return $app->server->serve();
    }
}

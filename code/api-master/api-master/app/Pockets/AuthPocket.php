<?php


namespace App\Pockets;


use App\Constant\ApiBusinessCode;
use App\Jobs\UpdateChannelDataJob;
use App\Models\Sms;
use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleClient;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Option;
use Google_Client;
use App\Models\UserAuth;
use Illuminate\Support\Facades\Hash;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Wechat;
use App\Models\UserReview;

class AuthPocket extends BasePocket
{
    private const AUTH_TYPE_MOBILE_QUICK = 'mobile_quick';  // 极光一键登录
    private const AUTH_TYPE_MOBILE_SMS   = 'mobile_sms';    // 手机短信登录
    private const AUTH_TYPE_PASSWORD     = 'password';      // 密码登录
    private const AUTH_TYPE_EMAIL        = 'email';         // 邮件验证码登录
    private const AUTH_TYPE_GOOGLE       = 'google';        // google登录

    /**
     * 校验极光一键登录
     *
     * @param  string  $token  极光认证token
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getVerifyJPushLoginToken(string $token)
    {
        $api       = 'https://api.verification.jpush.cn/v1/web/loginTokenVerify';
        $client    = (new GuzzleClient(['timeout' => 4]));
        $appName   = user_agent()->appName;
        $configRep = pocket()->config->getJPushConfigByAppName($appName);
        if (!$configRep->getStatus()) {
            return ResultReturn::failed(trans('messages.config_error'));
        }
        $config        = $configRep->getData();
        $Authorization = 'Basic ' . base64_encode($config['key'] . ':' . $config['secret']);
        try {
            $response = $client->post($api, [
                'headers' => ['Authorization' => $Authorization],
                'json'    => ['loginToken' => $token]
            ]);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }
        $body = json_decode($response->getBody()->getContents(), true);
        if ($body['code'] !== 8000) {
            return ResultReturn::failed($body['content']);
        }
        $prefix    = '-----BEGIN RSA PRIVATE KEY-----';
        $suffix    = '-----END RSA PRIVATE KEY-----';
        $result    = '';
        $encrypted = null;
        $key       = $prefix . "\n" . $config['private_key'] . "\n" . $suffix;
        openssl_private_decrypt(base64_decode($body['phone']), $result, openssl_pkey_get_private($key));
        if (!$result) {
            return ResultReturn::failed(trans('messages.not_get_mobile'));
        }

        return ResultReturn::success(
            ['phone' => $result, 'token' => $token, 'data' => $body]);
    }

    /**
     * 极光一键登录方式登录验证
     *
     * @param  string  $token
     * @param  string  $clientId
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function authByMobileQuick(string $token, string $clientId)
    {
        $jPushResp = pocket()->auth->getVerifyJPushLoginToken($token);
        if (!$jPushResp->getStatus()) {
            $prefix = trans('messages.login_failed_error') . ':';

            return $jPushResp->setMessage($prefix . $jPushResp->getMessage());
        }

        $mobile = $jPushResp->getData()['phone'];
        $code   = md5($token . $mobile);
        if (rep()->sms->m()->where('type', Sms::TYPE_MOBILE_QUICKLY)
            ->where('code', $code)->first()) {
            return ResultReturn::failed(trans('messages.quick_login_failed_error'));
        }

        $createData = [
            'type'       => Sms::TYPE_MOBILE_QUICKLY,
            'user_id'    => 0,
            'mobile'     => $mobile,
            'client_id'  => $clientId,
            'code'       => $code,
            'expired_at' => Carbon::now()->addMinutes(5)->timestamp
        ];

        $sms = rep()->sms->getQuery()->create($createData);
        $sms->setAttribute('auth_field', 'mobile');

        return ResultReturn::success($sms);
    }

    /**
     * 验证码登录方式验证
     *
     * @param  int     $mobile
     * @param  string  $code
     *
     * @return ResultReturn
     */
    private function authByMobileSms($mobile, $code)
    {
        if (pocket()->sms->whetherMobileBlock($mobile)) {
            return ResultReturn::failed(trans('messages.frequently_request_code_error'));
        }

        if (!in_array($mobile, pocket()->util->getTestMobile())) {
            $sms = rep()->sms->getSmsByTypeAndAuthFiled(Sms::TYPE_MOBILE_SMS, (int)$mobile,
                'mobile', ['id', 'mobile', 'code']);
            if (!$sms || ($code != $sms->code)) {
                pocket()->sms->recordMobileErrorTimes($mobile);

                return ResultReturn::failed(trans('messages.not_have_code_error'));
            }
        } else {
            $sms = new Sms(['mobile' => $mobile, 'code' => $code]);
        }

        $sms->setAttribute('auth_field', 'mobile');

        return ResultReturn::success($sms);
    }

    /**
     * 密码方式登录验证
     *
     * @param  string  $certificate
     * @param  string  $password
     * @param  string  $field
     *
     * @return ResultReturn
     */
    private function authByPassword($certificate, $password, $field)
    {
        $mobileUserInfo = rep()->user->getQuery()->where('deleted_at', 0)
            ->where('destroy_at', 0)->where($field, $certificate)
            ->first();
        if (!$mobileUserInfo) {
            return ResultReturn::failed(trans('messages.not_register_error'));
        }
        $checkPassResp = pocket()->userAuth->checkPassword($mobileUserInfo->id, $password);
        if (!$checkPassResp->getStatus()) {
            return $checkPassResp->setMessage(trans('messages.passowrd_error'));
        }

        $sms = new Sms([$field => $certificate]);
        $sms->setAttribute('auth_field', $field);

        return ResultReturn::success($sms);
    }

    /**
     * 邮箱验证码方式登录
     *
     * @param  string  $email
     * @param  string  $code
     *
     * @return ResultReturn
     */
    private function authByEmail(string $email, $code)
    {
        if (pocket()->sms->whetherMobileBlock($email)) {
            return ResultReturn::failed(trans('messages.frequently_request_code_error'));
        }

        $sms = rep()->sms->getSmsByTypeAndAuthFiled(Sms::TYPE_MOBILE_SMS, $email,
            'email', ['id', 'email', 'code']);
        if (!$sms || ($code != $sms->code)) {
            pocket()->sms->recordMobileErrorTimes($email);

            return ResultReturn::failed(trans('messages.not_have_code_error'));
        }

        $sms->setAttribute('auth_field', 'email');

        return ResultReturn::success($sms);
    }

    /**
     * Google Oauth2 登录
     *
     * @param  string  $idToken
     * @param  string  $clientId
     *
     * @return ResultReturn
     */
    private function authByGoogle(string $idToken, string $clientId)
    {
        $httpClient   = new GuzzleClient(['proxy' => config('custom.request_proxy')]);
        $googleClient = new Google_Client();
        $googleClient->setHttpClient($httpClient);

        try {
            $payload = $googleClient->verifyIdToken($idToken);
        } catch (\Exception $exception) {
            return ResultReturn::failed($exception->getMessage());
        }

        if ($payload === false) {
            return ResultReturn::failed('请通过正式的 google 授权进行登录');
        }

        $email      = $payload['email'];
        $createData = [
            'type'       => Sms::TYPE_MOBILE_QUICKLY,
            'email'      => $email,
            'client_id'  => $clientId,
            'code'       => md5($idToken . $email),
            'expired_at' => Carbon::now()->addMinutes(5)->timestamp
        ];

        $sms = rep()->sms->getQuery()->create($createData);
        $sms->setAttribute('auth_field', 'email');

        return ResultReturn::success($sms);
    }

    /**
     * 根据不同的登录方式选择不同验证
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $clientId
     * @param  string                    $type
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function authByType($request, $clientId, $type)
    {
        switch ($type) {
            case self::AUTH_TYPE_MOBILE_QUICK:
                $smsRespDataResp = pocket()->auth->authByMobileQuick($request->token, $clientId);
                break;
            case self::AUTH_TYPE_MOBILE_SMS:
                $smsRespDataResp = pocket()->auth->authByMobileSms($request->mobile, $request->code);
                break;
            case self::AUTH_TYPE_PASSWORD:
                $authField       = $request->has('email') ? 'email' : 'mobile';
                $certificate     = $request->{$authField};
                $smsRespDataResp = pocket()->auth->authByPassword($certificate,
                    $request->password, $authField);
                break;
            case self::AUTH_TYPE_EMAIL:
                $smsRespDataResp = pocket()->auth->authByEmail($request->email, $request->code);
                break;
            case self::AUTH_TYPE_GOOGLE:
                $smsRespDataResp = pocket()->auth->authByGoogle($request->token, $clientId);
                break;
            default:
                return ResultReturn::failed('不支持的登录方式～');
                break;
        }

        return $smsRespDataResp;
    }

    /**
     * 获取已注册的用户
     *
     * @param  int     $userId
     * @param  string  $clientId
     *
     * @return ResultReturn
     */
    public function getExistingUser(int $userId, string $clientId, $clientVersion)
    {
        $userInfo          = pocket()->account->getAccountInfo($userId, $clientVersion)->getData();
        $verifyIsBlackResp = pocket()->blacklist->verifyIsBlackByUserId($userInfo->id);
        if (!$verifyIsBlackResp->getStatus()) {
            return ResultReturn::failed($verifyIsBlackResp->getMessage(),
                ApiBusinessCode::BLACKLIST_LOCK);
        }

        [$checkStatus, $reason] = pocket()->user->getUserCheckStatus($userInfo->id);
        $userInfo->setAttribute('check_status', ['status' => $checkStatus]);
        pocket()->userDetail->updateUserClientId($userInfo->id, $clientId);
        $this->authedUserEvent($userInfo);

        return ResultReturn::success($userInfo);
    }

    /**
     * 创建未注册的用户
     *
     * @param          $certificate
     * @param  string  $authField
     * @param  string  $clientId
     * @param  string  $channel
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createNoExistingUser($certificate, string $authField, string $clientId, string $channel)
    {
        $openRegisterClientIds = pocket()->config->getOpenRegisterClientIds();
        if (!in_array($clientId, $openRegisterClientIds, true)) {
            if (app()->environment('production')
                && pocket()->userDetail->getUserCountByClientId($clientId) >= 2) {
                return ResultReturn::failed(trans('messages.equipment_register_limit'),
                    ApiBusinessCode::FORBID_COMMON);
            }
        }

        $user = pocket()->user->createNewUser($certificate, $authField, $clientId, $channel);
        //                pocket()->common->commonQueueMoreByPocketJob(pocket()->resource,
        //                    'postUserInviteCodeQrCode', [$user->id]);
        pocket()->common->commonQueueMoreByPocketJob(pocket()->stat, 'statUserRegister',
            [$user->id, time()], 10);
        $this->verifyChannel($channel, $user->id);
        $this->authedUserEvent($user);

        return ResultReturn::success($user);
    }

    /**
     * 认证之后的需要执行的操作
     *
     * @param  User  $userInfo
     */
    private function authedUserEvent(User $userInfo)
    {
        if (!$userInfo->invite_channel && $userInfo->userDetail->reg_schedule
            != UserDetail::REG_SCHEDULE_FINISH
            && pocket()->inviteRecord->checkUserIsAppletInvite($userInfo)) {
            $userInfo->setAttribute('invite_channel', 1);
        }

        $userInfo->setAttribute('has_set_password', pocket()->userAuth->hasPassword($userInfo->id));
        pocket()->account->appendStatusToUser($userInfo);

        pocket()->common->commonQueueMoreByPocketJob(pocket()->userDetail,
            'updateUserClientName', [$userInfo->id, user_agent()->userAgent]);
        if (in_array(user_agent()->os, ['ios', 'android'])) {
            rep()->userDetail->m()->where('user_id', $userInfo->id)
                ->update(['run_version' => user_agent()->clientVersion]);
        }
    }

    /**
     * 验证渠道
     *
     * @param  string  $channel
     * @param  int     $userId
     *
     * @return bool
     */
    private function verifyChannel($channel, $userId)
    {
        //因为针对每个appName的包来说. 每个包的promote和main都不一样 比如小圈就是 xiaoquan_promote
        if ($channel == '' || (strripos($channel, 'main') !== false)
            || (strripos($channel, 'promote') !== false)) {
            return true;
        }
        //上报到AD
        dispatch(new UpdateChannelDataJob('register', $userId))
            ->onQueue('update_channel_data');

        return true;
    }

    /**
     * 检查用户是否能访问此功能
     *
     * @param $userId
     * @param $route
     *
     * @return ResultReturn
     */
    public function canVisit($userId, $route)
    {
        $user     = rep()->admin->getById($userId);
        $userRole = $user->role_id;
        $option   = rep()->option->m()
            ->where('code', $route)
            ->where('type', Option::TYPE_BACK)
            ->first();
        if (!$option) {
            return ResultReturn::failed(trans('messages.api_not_config'));
        }
        $exist = rep()->authority->m()
            ->where('role_id', $userRole)
            ->where('option_id', $option->id)
            ->first();
        if (!$exist) {
            return ResultReturn::failed(trans('messages.user_can_look'));
        } else {
            return ResultReturn::success([]);
        }
    }

    /**
     * 获取用户人脸认证token
     *
     * @param $userId
     * @param $metaInfo
     *
     * @return ResultReturn|\Illuminate\Http\JsonResponse
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     */
    public function getUserFaceAuthToken($userId, $metaInfo)
    {
        $userBasePic = pocket()->account->getBasePic($userId);
        $user        = rep()->user->getById($userId, ['uuid']);
        //        $userAvatar     = rep()->resource->m()->where('related_id', $userId)->where('related_type',
        //            Resource::RELATED_TYPE_USER_AVATAR)->first();
        //        $avatarResource = cdn_url($userAvatar->resource);
        $bizId  = 'xiaoquan' . $user->uuid . '/' . (string)rand(1000, 9999);
        $result = pocket()->aliYun->smartAuthResponse($userBasePic, $userId, $bizId, $metaInfo);
        if ($result->getStatus() == false) {

            return api_rr()->serviceUnknownForbid($result->getMessage());
        }
        $data       = $result->getData();
        $createData = [
            'user_id'    => $userId,
            'request_id' => $data['RequestId'],
            'token'      => $data['ResultObject']['CertifyId'],
            'biz_id'     => $bizId
        ];
        rep()->loginFaceRecord->m()->create($createData);

        return ResultReturn::success($result->getData());
    }
}

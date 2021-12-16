<?php


namespace App\Http\Controllers;


use App\Models\Role;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Sms;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\AuthRequest;
use App\Http\Requests\Auth\PasswordRequest;
use App\Models\LoginFaceRecord;

class AuthController extends BaseController
{
    /**
     * 用户认证
     *
     * @param  AuthRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function auth(AuthRequest $request)
    {
        $type              = $request->query->get('type');
        $channel           = request()->headers->get('channel', '');
        $clientId          = $this->getClientId();
        $verifyIsBlackResp = pocket()->blacklist->verifyIsBlackByClientId($clientId);
        $appName           = user_agent()->appName;
        if (!$verifyIsBlackResp->getStatus()) {
            return api_rr()->userBlacklist($verifyIsBlackResp->getMessage());
        }

        $smsRespDataResp = pocket()->auth->authByType($request, $clientId, $type);
        if (!$smsRespDataResp->getStatus()) {
            return api_rr()->forbidCommon($smsRespDataResp->getMessage());
        }

        $smsRespData = $smsRespDataResp->getData();
        $authField   = $smsRespData->auth_field;
        $user        = rep()->user->getLatestUserByFiled(
            $smsRespData->{$authField},
            $authField,
            ['id', 'uuid', 'destroy_at']
        );
        in_array($appName, pocket()->config->getBecomeChannelAppName()) && $channel = $appName;
        $userInfoResp = $user instanceof User && ($user->destroy_at == 0 || time() < $user->destroy_at) ? pocket()->auth->getExistingUser(
            $user->id, $clientId, user_agent()->clientVersion) : pocket()->auth->createNoExistingUser(
            $smsRespData->{$authField}, $authField, $clientId, $channel);
        if (!$userInfoResp->getStatus()) {
            return api_rr()->customFailed(
                $userInfoResp->getMessage(),
                $userInfoResp->getData()
            );
        }

        $smsRespData->id && rep()->sms->getQuery()->where('id', $smsRespData->id)
            ->update(['used_at' => time()]);

        pocket()->sms->clearMobileErrorTimes($smsRespData->{$authField});
        $userInfo = $userInfoResp->getData();
        if ($userInfo->hide == User::AUTO_HIDE) {
            rep()->user->m()->where('id', $userInfo->id)->update(['hide' => User::SHOW]);
            pocket()->esUser->updateUserFieldToEs($userInfo->id, ['hide' => User::SHOW]);
            pocket()->netease->sendSystemMessage($userInfo->uuid,
                '由于之前太久未活跃，系统降低了你的排序减少曝光。现已恢复，多活跃才可以获得更多曝光，不方便时可前往“我的”→“设置”页面操作隐身~');
        }

        return api_rr()->getOK(
            $userInfo,
            trans('messages.login_success'),
            ['Auth-Token' => pocket()->user->getUserToken($userInfo)]
        );
    }

    /**
     * 用户认证V2
     *
     * @param  AuthRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function authV2(AuthRequest $request)
    {
        $type              = $request->query->get('type');
        $channel           = request()->headers->get('channel', '');
        $clientId          = $this->getClientId();
        $appName           = user_agent()->appName;
        $verifyIsBlackResp = pocket()->blacklist->verifyIsBlackByClientId($clientId);
        if (!$verifyIsBlackResp->getStatus()) {
            return api_rr()->userBlacklist($verifyIsBlackResp->getMessage());
        }

        $smsRespDataResp = pocket()->auth->authByType($request, $clientId, $type);
        if (!$smsRespDataResp->getStatus()) {
            return api_rr()->forbidCommon($smsRespDataResp->getMessage());
        }

        $smsRespData = $smsRespDataResp->getData();
        $authField   = $smsRespData->auth_field;
        $user        = rep()->user->getLatestUserByFiled($smsRespData->{$authField},
            $authField, ['id', 'uuid', 'role', 'destroy_at']);
        if ($user instanceof User && ($user->destroy_at == 0 || time() < $user->destroy_at)) {
            if (in_array(Role::KEY_CHARM_GIRL, explode(',', $user->role)) && app()->environment('production')) {
                $smsRespData->id && rep()->sms->getQuery()->where('id', $smsRespData->id)
                    ->update(['used_at' => time()]);
                $faceAuthToken = pocket()->auth->getUserFaceAuthToken($user->id,
                    $request->meta_info)->getData();

                return api_rr()->getOK(
                    ['user_info' => null, 'face_token' => $faceAuthToken]);
            }

            $userInfoResp = pocket()->auth->getExistingUser($user->id, $clientId, user_agent()->clientVersion);
        } else {
            in_array($appName, pocket()->config->getBecomeChannelAppName()) && $channel = $appName;
            $userInfoResp = pocket()->auth->createNoExistingUser($smsRespData->{$authField},
                $authField, $clientId, $channel);
        }

        if (!$userInfoResp->getStatus()) {
            return api_rr()->customFailed($userInfoResp->getMessage(),
                $userInfoResp->getData());
        }
        $smsRespData->id && rep()->sms->getQuery()->where('id', $smsRespData->id)
            ->update(['used_at' => time()]);
        pocket()->sms->clearMobileErrorTimes($smsRespData->{$authField});
        $userInfo = $userInfoResp->getData();

        return api_rr()->getOK(['user_info' => $userInfo, 'face_token' => null],
            trans('messages.login_success'),
            ['Auth-Token' => pocket()->user->getUserToken($userInfo)]);
    }

    /**
     * 检测登陆是否成功
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkFaceAuth(Request $request)
    {
        $token           = $request->post('certify_id');
        $clientId        = $this->getClientId();
        $loginFaceRecord = rep()->loginFaceRecord->m()->where('token', $token)->first();
        if (!$loginFaceRecord) {
            return api_rr()->notFoundResult('请先登录再进行人脸认证');
        }

        $faceAuth = pocket()->aliYun->smartAuthResult($token);
        if ($faceAuth->getStatus() == false) {
            return api_rr()->serviceUnknownForbid(trans('messages.get_face_detect_res_failed_error'));
        }

        $data           = $faceAuth->getData();
        $metaInfo       = json_decode($data['ResultObject']['MaterialInfo']);
        $passedScore    = $data['ResultObject']['PassedScore'];
        $faceAuthUpload = pocket()->account->uploadFaceAuth($metaInfo->facePictureInfo->pictureUrl);
        if ($faceAuthUpload->getStatus() == false) {
            return api_rr()->forbidCommon(trans('messages.upload_face_base_map_failed_error'));
        }
        $filePath = $faceAuthUpload->getData()->data->resource;

        if ($metaInfo->verifyInfo->faceComparisonScore >= 80 && $passedScore > 70) {
            $userInfoResp = pocket()->auth->getExistingUser(
                $loginFaceRecord->user_id, $clientId, user_agent()->clientVersion);
            if (!$userInfoResp->getStatus()) {
                return api_rr()->customFailed($userInfoResp->getMessage(),
                    $userInfoResp->getData());
            }
            $loginFaceRecord->update(
                ['login_status' => LoginFaceRecord::LOGIN_SUCCESS, 'face_pic' => $filePath]);

            $userInfo = $userInfoResp->getData();
            if ($userInfo->hide == User::AUTO_HIDE) {
                rep()->user->m()->where('id', $userInfo->id)->update(['hide' => User::SHOW]);
                pocket()->esUser->updateUserFieldToEs($userInfo->id, ['hide' => User::SHOW]);
                pocket()->netease->sendSystemMessage($userInfo->uuid,
                    '由于之前太久未活跃，系统降低了你的排序减少曝光。现已恢复，多活跃才可以获得更多曝光，不方便时可前往“我的”→“设置”页面操作隐身~');
            }

            return api_rr()->getOK($userInfo, trans('messages.login_success'),
                ['Auth-Token' => pocket()->user->getUserToken($userInfo)]);
        }
        $loginFaceRecord->update(
            ['login_status' => LoginFaceRecord::LOGIN_FAIL, 'face_pic' => $filePath]);

        return api_rr()->forbidCommon('人脸识别失败，请再次尝试');
    }

    /**
     * 构造假数据
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mock(Request $request)
    {
        $data = json_decode($request->input('data'), true);

        return $data;
    }

    /**
     * 验证不通过sharinstall渠道的推广数据
     *
     * @param $userId int 用户id
     *
     * @return bool
     */
    public function verifyChannel(int $userId)
    {
        $channel = request()->header('channel');
        //因为针对每个appName的包来说. 每个包的promote和main都不一样 比如小圈就是 xiaoquan_promote
        if ($channel == '' || (strripos($channel, 'main') !== false) || (strripos($channel, 'promote') !== false)) {
            return true;
        }
        //上报到AD
        dispatch(new UpdateChannelDataJob('register', $userId))->onQueue('update_channel_data');

        return true;
    }

    /**
     * 设置密码
     *
     * @param  PasswordRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setPassword(PasswordRequest $request)
    {
        $authUser = $this->getAuthUser();
        rep()->userDetail->m()->where('user_id', $authUser->id)
            ->where('reg_schedule', UserDetail::REG_SCHEDULE_PASSWORD)
            ->update([
                'reg_schedule' => UserDetail::REG_SCHEDULE_GENDER
            ]);
        pocket()->userAuth->resetPassword($this->getAuthUserId(), request('password'));

        return api_rr()->postOK([], trans('messages.set_success'));
    }


    /**
     * 重设密码
     *
     * @param  PasswordRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(PasswordRequest $request)
    {
        $authField = $request->get('type', 'mobile');
        $authUser  = rep()->user->m()->where($authField, $request->{$authField})
            ->where('destroy_at', 0)->first();

        $smsRespData = rep()->sms->getSmsByTypeAndAuthFiled(Sms::TYPE_RESET_PASSWORD,
            $request->{$authField}, $authField, ['id', $authField, 'code']);
        if (!$smsRespData || ($request->code != $smsRespData->code)) {

            return api_rr()->forbidCommon(trans('messages.not_have_code_error'));
        }
        pocket()->userAuth->resetPassword($authUser->id, request('password'));
        $smsRespData->update(['used_at' => time()]);

        return api_rr()->postOK((object)[], trans('messages.set_success'));
    }


    /**
     * 查看某手机号是否注册过
     *
     * @param  PasswordRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mobileRegister(Request $request)
    {
        $mobile = request('mobile');
        $exists = rep()->user->m()->where('mobile', $mobile)
            ->where('destroy_at', 0)->first();
        if (!$exists) {
            return api_rr()->notRegistered(trans('messages.curr_account_not_register'));
        }

        return api_rr()->postOK([],
            trans('messages.curr_account_registered'));
    }

    /**
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function registered(Request $request)
    {
        $authField   = $request->get('type', 'mobile');
        $certificate = $request->{$authField};
        $exists      = rep()->user->getQuery()->where($authField, $certificate)
            ->where('destroy_at', 0)->first();

        if (!$exists) {
            return api_rr()->notRegistered(trans('messages.curr_account_not_register'));
        }

        return api_rr()->postOK([],
            trans('messages.curr_account_registered'));
    }
}

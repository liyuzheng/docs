<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['prefix' => 'v99'], function () use ($router) {
    /** 解析二维码内容 */
    $router->post('parse/qrcode', ['as' => 'parse.qrcode', 'uses' => 'InternalController@parseQrCode']);
    /**
     * 获得邀请slave地址
     */
    $router->get('invite_bind_url/{inviteCode}', function ($inviteCode) {
        $domainsArr = pocket()->config->getSlaveInviteDomain();
        $domains    = [];
        foreach ($domainsArr as $item) {
            $domains[] = $item . '/d4' . '?ic=' . $inviteCode;
        }

        return api_rr()->getOK([
            'redirect_url' => array_random($domains, 1)[0]
        ]);
    });

    /**
     * 查询配置
     */
    $router->get('configs', function () {
        $settings   = rep()->config->m()
            ->select('key', 'value')
            ->whereIn('key', ['android_latest_url', 'apple_latest_url'])
            ->get();
        $returnData = [];
        foreach ($settings as $setting) {
            $returnData[$setting->key] = $setting->value;
        }

        return api_rr()->getOK($returnData);
    });

    /**
     * 邀请集群
     */
    $router->get('invite_slb', function () {
        $domains = [
            'http://ii1.xiaoquann.com/invite_user'
        ];

        return api_rr()->getOK([
            'redirect_url' => array_random($domains, 1)[0]
        ]);
    });

    /**
     * 查询短信验证码
     */
    $router->get('sms_code', function () {
        $smss = rep()->sms->m()
            ->where('mobile', 'like', '177%')
            ->select('mobile', 'code', 'created_at')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();
        foreach ($smss as $sms) {
            $sms->mobile   = substr_replace($sms->mobile, '****', 3, 4);
            $sms->event_at = date('Y-m-d H:i:s', $sms->created_at->timestamp);
        }

        return api_rr()->getOK($smss);
    });

    /**
     * 查询用户渠道
     */
    $router->get('channel', function () {
        if (!request()->has('mobile')) {
            return api_rr()->forbidCommon('参数错误');
        }
        $mobile = request('mobile');
        if (substr($mobile, 0, 3) != '177') {
            return api_rr()->forbidCommon('用户数据不能被查看');
        }
        $user = rep()->user->getUserByMobile($mobile);
        if (!$user || (time() - $user->created_at->timestamp) >= 7200) {
            return api_rr()->forbidCommon('用户数据不能被查看');
        }
        $userDetail = rep()->userDetail->getByUserId($user->id);

        return api_rr()->getOK([
            'nickname'   => $user->nickname,
            'channel'    => $userDetail->channel,
            'created_at' => date('Y-m-d H:i:s', $user->created_at->timestamp)
        ]);
    });
});

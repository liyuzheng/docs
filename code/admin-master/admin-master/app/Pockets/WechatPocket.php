<?php


namespace App\Pockets;


use App\Models\Wechat;
use EasyWeChat\Factory;
use GuzzleHttp\Client;
use App\Models\UserFollowOffice;
use GuzzleHttp\Exception\GuzzleException;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class WechatPocket extends BasePocket
{

    /**
     * 解析二维码内容并入库
     *
     * @param  Wechat  $wechat
     *
     * @return ResultReturn
     */
    public function postParseWeChat(Wechat $wechat)
    {
        if (!$wechat) {
            return ResultReturn::failed('not found');
        }
        $url = ltrim(parse_url($wechat->getOriginal('qr_code'), PHP_URL_PATH), '/');
        $api = config('custom.file_url') . 'v99/parse/qrcode';
        try {
            $response = (new Client(['timeout' => 10]))->post($api, [
                'json' => ['path' => $url]
            ]);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }
        if ($response->getStatusCode() !== 200) {
            return ResultReturn::failed('状态码不是200');
        }
        $body = json_decode($response->getBody()->getContents(), true);

        $wechat->update(['parse_content' => $body['data']['content']]);

        return ResultReturn::success(true);
    }

    /**
     * 生成微信公众号的access_token
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function postGeneralOfficeAccessToken()
    {
        $config = config('wechat.official_account.default');
        $api    = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $config['app_id'] . '&secret=' . $config['secret'];
        try {
            $response = $client = (new Client())->get($api);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }
        $body = json_decode($response->getBody()->getContents(), true);
        if (isset($body['errcode']) && $body['errcode']) {
            return ResultReturn::failed($body['errmsg']);
        }

        return ResultReturn::success([
            'access_token' => $body['access_token'],
            'expires_in'   => $body['expires_in']
        ]);
    }

    /**
     * 获得公众号access_token
     *
     * @return mixed|string
     */
    public function getOfficeAccessToken()
    {
        $redisKey    = config('redis_keys.office_access_token.key');
        $redisKeyTTL = config('redis_keys.office_access_token.ttl');
        if (redis()->exists($redisKey)) {
            return redis()->get($redisKey);
        }
        $response = pocket()->wechat->postGeneralOfficeAccessToken();
        if (!$response->getStatus()) {
            return '';
        }
        $responseData = $response->getData();
        redis()->set($redisKey, $responseData['access_token']);
        redis()->client()->expire($redisKey, $redisKeyTTL);

        return $responseData['access_token'];
    }

    /**
     * 获得临时二维码
     *
     * @param  int  $userId
     *
     * @return ResultReturn
     */
    public function getFollowOfficeQrCode(int $userId)
    {
        $now         = time();
        $config      = config('wechat.official_account.default');
        $weChatApp   = Factory::officialAccount($config);
        $accessToken = pocket()->wechat->getOfficeAccessToken();
        $weChatApp['access_token']->setToken($accessToken, 7200);
        $qrStringData = json_encode([
            'type' => UserFollowOffice::BIZ_TYPE_FOLLOW_BIND,
            'data' => ['user_id' => $userId]
        ]);
        try {
            $qrCodeResult = $weChatApp->qrcode->temporary($qrStringData, 29 * 24 * 3600);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }
        if (isset($qrCodeResult['errcode']) && $qrCodeResult['errcode']) {
            return ResultReturn::failed($qrCodeResult['errmsg']);
        }
        $insertArr = [
            'user_id'    => $userId,
            'ticket'     => $qrCodeResult['ticket'],
            'data'       => $qrStringData,
            'url'        => $qrCodeResult['url'],
            'status'     => UserFollowOffice::STATUS_DEFAULT,
            'expired_at' => $now + $qrCodeResult['expire_seconds']
        ];
        rep()->userFollowOffice->getQuery()->create($insertArr);

        return ResultReturn::success($qrCodeResult);
    }

    /**
     * 获得微信公众号app
     *
     * @return \EasyWeChat\OfficialAccount\Application
     */
    public function getWechatOfficeApp()
    {
        $config      = config('wechat.official_account.default');
        $weChatApp   = Factory::officialAccount($config);
        $accessToken = pocket()->wechat->getOfficeAccessToken();
        $weChatApp['access_token']->setToken($accessToken, 7200);

        return $weChatApp;
    }
}

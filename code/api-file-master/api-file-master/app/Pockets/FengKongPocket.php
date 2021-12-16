<?php


namespace App\Pockets;

use App\Foundation\Modules\Pocket\BasePocket;
use GuzzleHttp\Client;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class FengKongPocket extends BasePocket
{
    private static $guzzleInstance;

    /**
     * @return Client
     */
    public static function getGuzzleInstance()
    {
        if (!self::$guzzleInstance instanceof \GuzzleHttp\Client) {
            self::$guzzleInstance = new Client(['timeout' => 2]);
        }

        return self::$guzzleInstance;
    }


    /**
     * 初始化headers
     *
     * @return ResultReturn
     */
    private function initHeader()
    {
        $now     = strval(time());
        $nonce   = md5($now);
        $headers = [
            'Nonce'        => $nonce,
            'CurTime'      => $now,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        return ResultReturn::success($headers);
    }

    /**
     * 统一发送请求
     *
     * @param         $method
     * @param         $api
     * @param  array  $body
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function request($method, $api, array $body)
    {
        try {
            $client   = self::getGuzzleInstance();
            $response = $client->request($method, $api, [
                'headers' => $this->initHeader()->getData(),
                'body'    => json_encode($body),
                'timeout' => 3
            ]);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }
        $decodeResponse = json_decode($response->getBody()->getContents(), true);
        if ($response->getStatusCode() != 200) {
            return ResultReturn::failed(trans('messages.http_code_not_200_tmpl'),
                $decodeResponse);
        }
        if (isset($decodeResponse['code']) && $decodeResponse['code'] != 1100) {
            return ResultReturn::failed(trans('messages.business_code_not_1100_tmpl'),
                $decodeResponse);
        }

        return ResultReturn::success($decodeResponse);
    }

    /**
     * 发送视频检测请求
     *
     * @param $uuid
     * @param $url
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendPornCheckRequest($uuid, $url)
    {
        $api    = config('fengkong.video.check.api');
        $method = config('fengkong.video.check.method');
        $body   = [
            'accessKey' => config('fengkong.access_key'),
            'imgType'   => 'PORN',
            'audioType' => 'NONE',
            'btId'      => $uuid,
            'callback'  => get_api_uri('callback/fengkong/video'),
            //            'callback'  => 'https://lyx-api-dev.wqdhz.com/callback/fengkong/video',
            'data'      => [
                'url'             => $url,
                'detectFrequency' => 3,
                'retallImg'       => 1,
                'channel'         => 'VIDEO'
            ]
        ];

        return $this->request($method, $api, $body);
    }

    /**
     * 获取视频检测结果
     *
     * @param $bitId
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPornCheckResult($bitId)
    {
        $api    = config('fengkong.video.result.api');
        $method = config('fengkong.video.result.method');
        $body   = [
            'accessKey' => config('fengkong.access_key'),
            'btId'      => $bitId
        ];

        return $this->request($method, $api, $body);
    }
}

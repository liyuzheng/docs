<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Services\Guzzle\GuzzleHandle;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class YuanZhiPocket extends BasePocket
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
            'Content-Type' => 'application/json;charset=utf-8'
        ];

        return ResultReturn::success($headers);
    }

    /**
     * 发送请求
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
     * 获取通用发送参数
     *
     * @return array
     */
    private function getPublicBody()
    {
        $body = [
            'appkey'  => config('sms.yuanZhi.appKey'),
            'appcode' => config('sms.yuanZhi.appCode'),
            'sign'    => $this->getSign()
        ];

        return $body;
    }

    /**
     * 获取签名
     *
     * @return string
     */
    private function getSign()
    {
        return md5(config('sms.yuanZhi.appKey') . config('sms.yuanZhi.appSecret') . intval(microtime(true) * 1000));
    }

    /**
     * 发送短信
     *
     * @param $phone
     * @param $msg
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendMsg($phone, $msg)
    {
        $uid               = pocket()->util->getSnowflakeId();
        $body              = $this->getPublicBody();
        $body['uid']       = $uid;
        $body['phone']     = $phone;
        $body['msg']       = $msg;
        $body['timestamp'] = intval(microtime(true) * 1000);

        return $this->request(config('sms.yuanZhi.method'), config('sms.yuanZhi.api'), $body);
    }

    /**
     * 批量发送短信
     *
     * @param $uid
     * @param $phones
     * @param $msg
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendBatchMsg($uid, $phones, $msg)
    {
        $body              = $this->getPublicBody();
        $body['uid']       = $uid;
        $body['phone']     = implode(',', $phones);
        $body['msg']       = $msg;
        $body['timestamp'] = intval(microtime(true) * 1000);

        return $this->request(config('sms.yuanZhi.method'), config('sms.yuanZhi.api'), $body);
    }
}

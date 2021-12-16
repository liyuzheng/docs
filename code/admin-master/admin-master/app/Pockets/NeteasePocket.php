<?php
/**
 * Created by PhpStorm.
 * User: ailuoy
 * Date: 2019/3/8
 * Time: 下午3:32
 */

namespace App\Pockets;

use GuzzleHttp\Client;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class NeteasePocket extends BasePocket
{
    private static $guzzleInstance;

    /**
     * @return Client
     */
    public static function getGuzzleInstance() : Client
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
    private function initHeader() : ResultReturn
    {
        $now     = strval(time());
        $nonce   = md5($now);
        $headers = [
            'AppKey'       => config('custom.netease.app_key'),
            'Nonce'        => $nonce,
            'CurTime'      => $now,
            'CheckSum'     => sha1(config('custom.netease.app_secret') . $nonce . $now),
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
    private function request($method, $api, array $body) : ResultReturn
    {

        try {
            $body     = get_url_query($body);
            $client   = self::getGuzzleInstance();
            $response = $client->request($method, $api, [
                'headers' => $this->initHeader()->getData(),
                'body'    => $body,
                'timeout' => 3
            ]);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }
        $decodeResponse = json_decode($response->getBody()->getContents(), true);
        if ($response->getStatusCode() != 200) {
            return ResultReturn::failed('HTTP请求不是200', $decodeResponse);
        }
        if (isset($decodeResponse['code']) && $decodeResponse['code'] != 200) {
            return ResultReturn::failed('业务code不是200', $decodeResponse);
        }

        return ResultReturn::success($decodeResponse);
    }

    /**
     * 发送自定义消息
     *
     * @param         $sUUid
     * @param         $rUUid
     * @param  array  $body
     * @param  array  $extension
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     * https://dev.yunxin.163.com/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E6%B6%88%E6%81%AF%E5%8A%9F%E8%83%BD?#%E5%8F%91%E9%80%81%E6%99%AE%E9%80%9A%E6%B6%88%E6%81%AF
     */
    public function msgSendCustomMsg($sUUid, $rUUid, array $body, array $extension = []) : ResultReturn
    {
        $method = config('netease.api.msg.sendMsg.method');
        $api    = config('netease.api.msg.sendMsg.api');
        $body   = [
            'from'     => $sUUid,
            'ope'      => 0,
            'to'       => $rUUid,
            'type'     => 100,
            'body'     => json_encode($body),
            'useYidun' => 0,
            'option'   => json_encode([
                'roam'       => false,
                'sendersync' => true,
            ])
        ];

        if ($extension) {
            if (isset($extension['option'])) {
                $_options = $extension['option'];
                $option   = json_decode($body['option'], true);
                foreach ($_options as $key => $_option) {
                    $option[$key] = $_option;
                }
                $body['option'] = json_encode($option);
            }

            if (isset($extension['payload'])) {
                $body['payload'] = $extension['payload'];
            }

            if (isset($extension['pushcontent'])) {
                $body['pushcontent'] = $extension['pushcontent'];
            }
        }

        return $this->request($method, $api, $body);
    }

    /**
     * 发送普通消息
     *
     * @param         $sUUid
     * @param         $rUUid
     * @param         $message
     * @param  array  $extension
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     * http://dev.netease.im/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E6%B6%88%E6%81%AF%E5%8A%9F%E8%83%BD?#%E5%8F%91%E9%80%81%E6%99%AE%E9%80%9A%E6%B6%88%E6%81%AF
     */
    public function msgSendMsg($sUUid, $rUUid, $message, array $extension = []) : ResultReturn
    {
        $method = config('netease.api.msg.sendMsg.method');
        $api    = config('netease.api.msg.sendMsg.api');
        $body   = [
            'from'     => $sUUid,
            'ope'      => 0,
            'to'       => $rUUid,
            'type'     => 0,
            'body'     => json_encode(['msg' => urlencode(str_replace("\"", "'", $message))]),
            'useYidun' => 0,
            'option'   => json_encode([
                'roam'       => false,
                'sendersync' => true,
            ])
        ];
        if ($extension) {
            if (isset($extension['option'])) {
                $_options = $extension['option'];
                $option   = json_decode($body['option'], true);
                foreach ($_options as $key => $_option) {
                    $option[$key] = $_option;
                }
                $body['option'] = json_encode($option);
            }

            if (isset($extension['payload'])) {
                $body['payload'] = $extension['payload'];
            }
        }

        return $this->request($method, $api, $body);
    }

    /**
     * 修改用户信息
     *
     * @param         $uuid
     * @param         $nickname
     * @param         $avatar
     * @param  array  $mores
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * http://dev.netease.im/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID?#%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID%E6%9B%B4%E6%96%B0
     */
    public function userUpdateUinfo($uuid, $nickname = '', $avatar = '', $mores = []) : ResultReturn
    {
        $method = config('netease.api.user.updateUinfo.method');
        $api    = config('netease.api.user.updateUinfo.api');
        $body   = [
            'accid' => $uuid
        ];
        if ($nickname) {
            $body['name'] = $nickname;
        }
        if ($avatar) {
            $body['icon'] = $avatar;
        }

        if ($mores) {
            foreach ($mores as $key => $more) {
                $body[$key] = $more;
            }
        }

        return $this->request($method, $api, $body);
    }

    /**
     * 封禁网易云通信ID
     *
     * @param $uuid
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     * http://dev.netease.im/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID?#%E5%B0%81%E7%A6%81%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID
     */
    public function userBlock($uuid) : ResultReturn
    {
        $method = config('netease.api.user.block.method');
        $api    = config('netease.api.user.block.api');
        $body   = [
            'accid'    => $uuid,
            'needkick' => 'true'
        ];

        return $this->request($method, $api, $body);
    }

    /**
     * 解禁网易云通信ID
     *
     * @param $uuid
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * http://dev.netease.im/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID?#%E8%A7%A3%E7%A6%81%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID
     */
    public function userUnblock($uuid) : ResultReturn
    {
        $method = config('netease.api.user.unblock.method');
        $api    = config('netease.api.user.unblock.api');
        $body   = [
            'accid' => $uuid,
        ];

        return $this->request($method, $api, $body);
    }

    /**
     * 消息撤回
     *
     * @param       $msgId       撤回的消息ID
     * @param       $createTime  消息的创建时间
     * @param       $from        发送者ID
     * @param       $to          接收者ID
     * @param  int  $ignoreTime  是否忽略时间限制
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function recall($msgId, $createTime, $from, $to, $ignoreTime = 1) : ResultReturn
    {
        $method = config('netease.api.msg.recall.method');
        $api    = config('netease.api.msg.recall.api');
        $body   = [
            'deleteMsgid' => $msgId,
            'timetag'     => $createTime,
            'type'        => 7,
            'from'        => $from,
            'to'          => $to,
            'ignoreTime'  => $ignoreTime
        ];

        return $this->request($method, $api, $body);
    }
}

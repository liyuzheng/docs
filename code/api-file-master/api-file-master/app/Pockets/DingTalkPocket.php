<?php


namespace App\Pockets;

use App\Foundation\Modules\Pocket\BasePocket;
use GuzzleHttp\Client;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class DingTalkPocket extends BasePocket
{
    /**
     * 发送POST请求
     *
     * @param $remote_server
     * @param $post_string
     *
     * @return bool|string
     */
    function request_by_curl($remote_server, $post_string)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
        // curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    /**
     * 发送普通消息
     *
     * @param $url
     * @param $message
     *
     * @return bool|string
     */
    public function sendSimpleMessage($url, $message)
    {
        $data        = array('msgtype' => 'text', 'text' => array('content' => $message));
        $data_string = json_encode($data);

        return $this->request_by_curl($url, $data_string);
    }
}

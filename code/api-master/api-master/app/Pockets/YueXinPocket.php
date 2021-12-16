<?php


namespace App\Pockets;


use GuzzleHttp\Client;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class YueXinPocket extends BasePocket
{
    /**
     * 发送ios掉包召回短信
     *
     * @param  array  $mobiles
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendIosDownCallSms(array $mobiles)
    {
        $mtTime   = date("YmdHis", time());
        $args     = [
            'Name'       => '7pd7k06l',
            'Pwd'        => md5('50d312c90c499ee09e496b457868ffc1' . $mtTime),
            'Mttime'     => $mtTime,
            'SignId'     => '773122d4e33f4dc187d1a8037821cfce',
            'TemplateId' => '231cb50ad03e4987aa42943cc9c0f22a',
            'Variables'  => [
                'code' => 8888
            ],
            'Phones'     => implode(',', $mobiles),
        ];
        $api      = 'http://106.14.55.160:9000/HttpSmsTmptMt';
        $response = (new Client())->post($api, [
            'json' => $args
        ]);
        if ($response->getStatusCode() !== 200) {
            return ResultReturn::failed('http code 不是200');
        }

        return ResultReturn::success(json_decode($response->getBody()->getContents(), true));
    }
}

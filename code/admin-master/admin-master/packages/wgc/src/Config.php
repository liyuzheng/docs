<?php

namespace WGCYunPay;

class Config
{
    /**
     * 填 商户代码（由综合服务平台分配） 云账户·综合服务平台获取
     * @var string
     */
    public $dealer_id  = '';

    /**
     * 每个 request 的 id，要求每次请求 id 不⼀样，会在 response 中原样返回
     * @var string
     */
    public $request_id = '';

    /**
     * 代征主体(必填) 云账户·综合服务平台获取
     * @var string
     */
    public $broker_id  = '';

    /**
     * 随机数，⽤于签名
     * @var int
     */
    public $mess       = 0;

    /**
     * 时间戳，精确到秒
     * @var int
     */
    public $timestamp  = 0;

    /**
     * 加密key 云账户·综合服务平台获取
     * @var string
     */
    public $des3_key   = '';

    /**
     * 签名 云账户·综合服务平台获取
     * @var string
     */
    public $app_key    = '';
}

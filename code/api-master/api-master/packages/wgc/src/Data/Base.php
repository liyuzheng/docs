<?php


namespace WGCYunPay\Data;


class Base
{
    /**
     * 请求路由
     * @var string
     */
    protected $route  = '';

    /**
     * 请求方式
     * @var string
     */
    protected $method = 'get';

    /**
     * 获取请求路由
     * Date : 2019/8/1 13:58
     * @return array
     */
    public function getRoute()
    {
        return [$this->route, $this->method];
    }
}

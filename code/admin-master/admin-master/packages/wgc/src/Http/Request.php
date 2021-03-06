<?php

namespace WGCYunPay\Http;

use WGCYunPay\Data\Router;

class Request
{
    private $url;

    private $header         = [];

    private $output;

    private $http_info;

    private static $timeout = 30;

    private $curl_ch;

    public function __construct(string $router)
    {
        $this->url = Router::getRouter($router);
    }

    public function setTimeout(int $timeout = 30)
    {
        static::$timeout = $timeout;
        return $this;
    }

    public function setHeader(array $header = [])
    {
        $this->header = $header;
        return $this;
    }

    public function addHeader(array $header = [])
    {
        $this->header = array_merge($this->header, $header);
        return $this;
    }


    public function getBody()
    {
        return $this->output;
    }

    public function getBodyJson()
    {
        return json_decode($this->output, true);
    }

    public function getHttpInfo()
    {
        return $this->http_info;
    }


    private function curlInit()
    {
        $this->curl_ch = curl_init();
        curl_setopt($this->curl_ch, CURLOPT_URL, $this->url);
        if (substr($this->url, 0, 5) == 'https') {
            curl_setopt($this->curl_ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($this->curl_ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($this->curl_ch, CURLOPT_TIMEOUT, static::$timeout);
        if ($this->header) {
            curl_setopt($this->curl_ch, CURLOPT_HTTPHEADER, $this->header);
        }
    }

    private function curlClose()
    {
        curl_close($this->curl_ch);
    }

    public function get(array $data)
    {
        $this->url .= '?'.http_build_query($data);
        $this->curlInit();
        curl_setopt($this->curl_ch, CURLOPT_HEADER, 0);
        curl_setopt($this->curl_ch, CURLOPT_NOBODY, 0);
        //只取body头
        curl_setopt($this->curl_ch, CURLOPT_RETURNTRANSFER, 1);
        $this->output = curl_exec($this->curl_ch);
        $this->http_info = curl_getinfo($this->curl_ch);
        $this->curlClose();
        return $this;
    }

    public function post(array $data = [])
    {
        $this->curlInit();
        curl_setopt($this->curl_ch, CURLOPT_POST, true);
        curl_setopt($this->curl_ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($this->curl_ch, CURLOPT_RETURNTRANSFER, 1);
        $this->output = curl_exec($this->curl_ch);
        $this->http_info = curl_getinfo($this->curl_ch);
        $this->curlClose();
        return $this;
    }
}

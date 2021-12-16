<?php


namespace WGCYunPay\AbstractInterfaceTrait;

use WGCYunPay\Config;
use WGCYunPay\Http\Request;
use WGCYunPay\Service\Des3Service;
use WGCYunPay\Util\RSAUtil;
use WGCYunPay\Util\SignUtil;

abstract class BaseService implements ServiceInterface
{
    /**
     * 相关配置
     * @var Config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * 待加密数据
     * Date : 2019/7/31 15:29
     * @return mixed
     */
    abstract protected function getDes3Data();
    abstract protected function getRequestInfo();

    protected function getHeader()
    {
        return [
            'Content-Type: application/x-www-form-urlencoded',
            "dealer-id: {$this->config->dealer_id}",
            "request-id: {$this->config->request_id}",
        ];
    }

    protected function getRequestData()
    {
        $desData  = Des3Service::encode($this->getDes3Data(), $this->config->des3_key);
        $signData = "data=".$desData."&mess=".$this->config->mess."&timestamp=".$this->config->timestamp."&key=".$this->config->app_key;

        $rsa = new RSAUtil($this->config);
        $sign = $rsa->sign($signData);

        $postData              = [];
        $postData['data']      = $desData;
        $postData['mess']      = $this->config->mess;
        $postData['timestamp'] = $this->config->timestamp;
        $postData['sign']      = $sign;
        $postData['sign_type'] = 'rsa';
        return $postData;
    }

    public function execute($callback = null)
    {
        $requestData = $this->getRequestData();
        $header      = $this->getHeader();
        $requestInfo = $this->getRequestInfo();
        $method      = $requestInfo[1] ?? 'get';
        $request  = new Request($requestInfo[0]);
        $data     = $request
                  ->setHeader($header)
                  ->$method($requestData)
                  ->getBodyJson();
//        echo "RequestData:";
//        var_dump($requestData);
      //  echo "RequestInfo:";
      //  var_dump($requestInfo);
        if($callback!==null && is_callable($callback)){
            return call_user_func($callback, $data);
        }

        if(method_exists($this, 'callback')){
            return call_user_func([$this, 'callback'], $data);
        }

        return $data;
    }
}

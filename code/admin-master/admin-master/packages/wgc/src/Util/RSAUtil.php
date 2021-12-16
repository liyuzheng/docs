<?php


 namespace WGCYunPay\Util;

 use WGCYunPay\Config;



 class RSAUtil
 {
     /**
      * 相关配置
      * @var Config
      */
     protected $config;
     /**
      * 公钥
      * @var
      */
     private $public_key;


     /**
      * 私钥
      * @var
      */
     private $private_key;



     /**
      * 初始化配置
      * RsaService constructor.
      * @param bool $type 默认私钥加密
      */
     public function __construct(Config $config,$type = true)
     {
         $this->config = $config;
         if ($type) {
             $this->private_key = $this->getPrivateKey();
             $this->public_key = $this->getPublicKey();
         }
     }


     /**
      * 配置私钥
      * openssl_pkey_get_private这个函数可用来判断私钥是否是可用的，可用，返回资源
      * @return bool|resource
      */
     private function getPrivateKey()
     {

         $privateKey  = openssl_get_privatekey($this->config->private_key);
         if(!$privateKey){
             die('私钥不可用');
         }
         return $privateKey;
     }


     /**
      * 配置公钥
      * openssl_pkey_get_public这个函数可用来判断私钥是否是可用的，可用，返回资源
      * @return resource
      */
     public function getPublicKey()
     {

         $publicKey = openssl_pkey_get_public($this->config->public_key);
         if(!$publicKey){
             die('公钥不可用');
         }

         return  $publicKey ;
     }



     /**
      * 签名算法
      * @access public
      * @param $data
      * @return string
      */
     public function sign($data){
         $res=openssl_get_privatekey($this->getPrivateKey());
         if($res)
         {
             openssl_sign($data, $sign,$res,"SHA256");
             openssl_free_key($res);
         }else {
             exit("私钥格式有误");
         }
         $sign = base64_encode($sign);
         return $sign;

     }

     /**
      * 验签
      * @access public
      * @param $data
      * @return
      */
     public function verify($response){
         $signData = "data=".$response['data']."&mess=".$response['mess']."&timestamp=".$response['timestamp']."&key=".$this->config->app_key;
         //echo  $signData;
         $result = (bool)openssl_verify( $signData, base64_decode($response['sign']), $this->getPublicKey(),"SHA256");
         return $result;
     }

     /**
      * 私钥解密   用于个税下载、发票下载文件解压密码的解密
      */
     public function privateDecrypt($data)
     {
         openssl_private_decrypt(base64_decode($data),$decrypted, $this->getPrivateKey());

         return  $decrypted;
     }
 }

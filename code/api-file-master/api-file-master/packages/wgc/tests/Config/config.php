<?php

$config = new \WGCYunPay\Config();
//商户ID   登录云账户综合服务平台在商户中心-》商户管理-》对接信息中查看
$config->dealer_id  = '05****';
//综合服务主体ID   登录云账户综合服务平台在商户中心-》商户管理-》对接信息中查看
$config->broker_id  = 'yi****';
//商户app key   登录云账户综合服务平台在商户中心-》商户管理-》对接信息中查看
$config->app_key    = 'Xv22****';
//商户3des key   登录云账户综合服务平台在商户中心-》商户管理-》对接信息中查看
$config->des3_key   = 'TK70****';
//商户私钥  商户使用OpenSSL自行生成的RSA2048秘钥 ，生成的商户公钥需要配置在云账户综合服务平台在商户中心-》商户管理-》对接信息-》商户公钥
$config->private_key   ='-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAMKvGra+c9SeW66U
/CvdED6WTk0dDK0zI6WxzTgUgwmyOq5BUeJDgS1gInNxRw2VTo/hElV84yE8zBP9
CRbUUQJ4OcjknTJ9AvgGrxFE9LStiP00KIuGYr5XHuU0OUsHE8ytH5fKnJNegAJu
******
/9ttCOraERc7wm3/3RSwH7tIQu/9fgh6/ej8PCXw6GD1YTTrbMeV1U8CQE5P+LYz
oi9V/Aw5Y50XydNE/PBiieU67FBepSqb+kRY5JmT0gfHvHHTVQjh1GKrJGZ5j/mY
Qk9YOhEnaRN/s1g=
-----END PRIVATE KEY-----';
//云账户公钥 登录云账户综合服务平台在商户中心-》商户管理-》对接信息中查看（每个商户ID对应的云账户公钥不同）
$config->public_key ='-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDCrxq2vnPUnluulPwr3RA+lk5N
*******
QF2jzD53n9NqLYlT2wIDAQAB
-----END PUBLIC KEY-----';
$config->mess       =\WGCYunPay\Util\StringUtil::round(10);
$config->timestamp  = time();
$config->request_id = \WGCYunPay\Util\StringUtil::round(10);
return $config;
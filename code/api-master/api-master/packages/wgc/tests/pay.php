<?php

require_once __DIR__ . '/bootstrap.php';

//  打款接口
//// 支付宝实时下单
$AliPayData = new \WGCYunPay\Data\Pay\AliPayData();
$AliPayData->order_id   =  "123456123";                # 商户订单号，由商户保持唯⼀性(必填)，64个英⽂字符以内
$AliPayData->real_name  = "张喵喵";                  # 姓名(必填)
$AliPayData->id_card    = "120110199901018888";     # 身份证(必填)
$AliPayData->pay        = "100.00";                 # 打款⾦额（单位为元, 必填）
$AliPayData->pay_remark = "备注";                    # 打款备注(选填，最⼤20个字符，⼀个汉字占2个字符，不允许特殊字符：' " & | @ % * ( ) - : # ￥)
$AliPayData->card_no    = "123@163.com";            # 收款⼈⽀付宝账户(必填)
$AliPayData->check_name    = "Check";               # 校验⽀付宝账户姓名，可填 Check、NoCheck
$AliPayData->notify_url    = "http://www.xxx.com";  # 回调地址(选填，最⼤⻓度为200)

$GoPay = new \WGCYunPay\Service\PayService($config, $AliPayData);
$result = $GoPay->execute();
echo "<br>支付宝下单：<br>";
var_dump($result);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

// 银行卡实时下单
$BankPayData = new \WGCYunPay\Data\Pay\BankPayData();
$BankPayData->order_id   = "1234567123";                         # 商户订单号，由商户保持唯⼀性(必填)，64个英⽂字符以内
$BankPayData->real_name  =  "张喵喵";                          # 银⾏开户姓名(必填)
$BankPayData->id_card    = "120110199901018888";              # 身份证(必填)
$BankPayData->card_no    = "6214838888888888";                # 银行卡号，只支持借记卡(必填)
$BankPayData->phone_no    = "18600000000";                    # ⽤户或联系⼈⼿机号(选填)
$BankPayData->pay        = "100.00";                          # 打款⾦额（单位为元, 必填）
$BankPayData->pay_remark = "备注";                             # 打款备注(选填，最⼤20个字符，⼀个汉字占2个字符，不允许特殊字符：' " & | @ % * ( ) - : # ￥)
$BankPayData->notify_url    = "http://www.xxx.com";           # 回调地址(选填，最⼤⻓度为200)

$GoPay = new \WGCYunPay\Service\PayService($config, $BankPayData);

$r1 = $GoPay->execute();
echo "<br>银行卡下单：<br>";
var_dump($r1);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";


// 企业付款到微信零钱实时下单
$WxPayData = new \WGCYunPay\Data\Pay\WxPayData();
$WxPayData->order_id   =  "12345678123";                    # 商户订单号，由商户保持唯⼀性(必填)，64个英⽂字符以内
$WxPayData->real_name  = "张喵喵";                           # 姓名(必填)
$WxPayData->id_card    = "120110199901018888";              # 身份证(必填)
$WxPayData->pay        =  "100.00";                         # 打款⾦额（单位为元, 必填）
$WxPayData->pay_remark = "备注";                             # 打款备注(选填，最⼤20个字符，⼀个汉字占2个字符，不允许特殊字符：' " & | @ % * ( ) - : # ￥)
$WxPayData->openid    = "fhjgdu_hue834nshua";               # 商户AppID下，某⽤户的openid(必填)
$WxPayData->wx_app_id    = "wx_dhhsrguer8947";              # 微信打款商户微信AppID(选填，最⼤⻓度为200)  注：若商户在云账户绑定了多个appid，则此处需指定appid
$WxPayData->wxpay_mode    = "transfer";                     # 微信打款模式(必填，固定值：transfer)
$WxPayData->notify_url    = "http://www.xxx.com";           # 回调地址(选填，最⼤⻓度为200)

$GoPay = new \WGCYunPay\Service\PayService($config, $WxPayData);

$r2 = $GoPay->execute();
echo "<br>微信下单：<br>";

var_dump($r2);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

// 查询订单
$order = new \WGCYunPay\Service\OrderService($config);
$r3 = $order
    ->setOrderId("123456123")              # 商户订单号，由商户保持唯⼀性(必填)，64个英⽂字符以内
    ->setchannel("支付宝")                  # 银⾏卡，⽀付宝，微信(不填时为银⾏卡订单查询)(选填)
    ->setDataType("encryption")            # 如果为encryption，则对返回的data进行加密(选填)
    ->execute();
echo "<br>单笔订单查询：<br>";
var_dump($r3);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

//// 查询商户余额
$accounts = new \WGCYunPay\Service\OrderService($config);
$r4 = $accounts
    ->setMethodType("query-accounts")
    ->execute();

echo "<br>商户余额查询：<br>";
var_dump($r4);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

// 查询电子回单
$receipt = new \WGCYunPay\Service\OrderService($config);
$r5 = $receipt
    ->setOrderId("123456")         # 商户订单号（商户订单号和综合服务平台订单号必须⼆选⼀）
    ->setRef("12345678990")           # 平台订单号（商户订单号和综合服务平台订单号必须⼆选⼀）
    ->setMethodType("receipt")
    ->execute();
echo "<br>查询电子回单：<br>";
var_dump($r5);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

// 取消待打款订单
$cancel = new \WGCYunPay\Service\OrderService($config);
$r6 = $cancel
    ->setOrderId("123456")            # 商户订单号（商户订单号和综合服务平台订单号必须⼆选⼀）
    ->setRef("12345678990")              # 平台订单号（商户订单号和综合服务平台订单号必须⼆选⼀）
    ->setChannel("支付宝")            # 银⾏卡，⽀付宝，微信(不填时为银⾏卡订单查询)(选填)
    ->setMethodType("order_fail")
    ->execute();
echo "<br>取消待打款订单：<br>";
var_dump($r6);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";


//回调+验签
$notifyJsonData = '{"data":"RDthP6Va2vzHbAjAc5S17BozGK4AxlvWJbWpVdo/GGg1OZR96ga3U+fsB34bIkQtecZIr7yrsfMBL1RXCn+Vri/OjA0dSDFhe+LNhmy+NWw6r6qzyXetBVi0fgCFIg4rk8wrG4ZAzHt3UF2o/Zvm3DX5oHHySvBHuo4Xu5Lm4V2sQFyE8G4Y9HnN+Fe11EqvrPmf2uoPw4NANkLchZNQwwvLHqknqgD3+4gpuf6UXjyhhDvs1Icz6x3Uef001wnUmMDAWwDWfyRWFsiI3NP0KfBlOTj/oGfIGzV32X5uAn8kbXegbuql890muZVD1ik6QEh5Stl78ne3jKvJlzfoMx95N0qrfmlhlTeZ+ouu2/3112gFyVlzwJtMpPAYv/Rt8NB9dy0H05Hgg1bLEGI8HCy71HjKeZY2mvXHmvt38cTWu2gctwukAaJZZjOYMfWq61YmSmewMWsU/UEOZqLaS/QNxC0p319EgQ3WDzj0oYEev0qE2Zmzjes+Hky0DZ5G7giCYpvIJnM+TD/pGLZ+dzqaouu8mo+GKxbRDWi88EJYPxvEmNiSfKKusgAHVsG4QNp0Q21lguqzD2wUcjmzJnKHPCs7zqro4R/oyM+WKUQo4EvBEPZpunGtVsX0AQpOH9ipFa+b6u9O8OZn6zsI177KCnJo5AR73YXcSWGBFaGtrgTNCIsPgujulGNHFKLkBOuAzc40KhEm7JBFiXiX9H1bvb415yedDTFvPTTPVyGkQ+i/Km3L0yr/eCxnMWTKAS1YPijhPg/ybhi+8KfTVHFnlEzvsLmNlhaju7TJyuaOeazL9iNfaT/nCxcUi2hh1ZMW0eI0+iU97IQM6uNNloe3LcTptE6uMqeXha7mR3iPrbIyQgTl2t49n4JTmIcoFAmr1Hb4oM+CWLrhuYN8SM4nPo1G/lKd","mess":"3361023","timestamp":"1594897034","sign":"LKWfx/XQLlribLPkf30PB56trFgangdcS2ypj/Ilmzf0eotJ7ZxNmzxoE0nuKR5OOo99YG+wpF9lDVszzdRZZ1uuKHWXvcXMuDh40bN6tMdLoH5G2wBlTrKWe/3AoG+P/xuVng/HdHHyXDwBynTBLBIrzDYNjrxUHz3U5HAWmvo=","sign_type":"rsa"}';
$notifyData = json_decode($notifyJsonData, true);
var_dump($notifyData);
$datainfo = \WGCYunPay\Service\Des3Service::decode($notifyData['data'], $config->des3_key);
$result= new \WGCYunPay\Util\RSAUtil($config);
$verifyResult=$result->verify($notifyData);
 echo"===================================================================================<br>回调解密：<br>";
 var_dump($datainfo);

echo"<br>===================================================================================<br>验签结果：<br>";
var_dump($verifyResult);

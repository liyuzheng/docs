<?php

require_once __DIR__ . '/bootstrap.php';

//  H5 用户签约
// H5预申请签约接⼝
$presign = new \WGCYunPay\Service\H5SignService($config);
$s1 = $presign
    ->setUid("uid_123")                    # 商户app端⽤户id，不能重复   （必填）
    ->setRealName("张三")              # ⽤户真实姓名               （必填）
    ->setIdCard("120114199912121234")   # 证件号码                   （必填）
    ->setCertificateType(0)       # 证件类型，0-身份证，2港澳居⺠来往内地通⾏证，3-护照，5-台湾居⺠来往⼤陆通⾏证  （必填）
    ->setMethodType("presign")
    ->execute();

echo "<br>H5预申请签约接口：<br>";
var_dump($s1);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

//2）H5签约接⼝
$sign = new \WGCYunPay\Service\H5SignService($config);
$s2 = $sign
    ->setToken("123123")               # H5预申请签约接⼝返回的token
    ->setColor("#817ff")               # H5⻚⾯主题颜⾊，传空时默认为蓝⾊
    ->setUrl("http:www.xxx.com")         # 回调url地址，post请求，⽤户签约完成之后，回调商户通知签约完成
    ->setRedirectUrl("http:www.xxx.com")  # 跳转url，签约完成之后通过此url跳回商户指定的⻚⾯，如果redirect_url为空，将会执⾏ jsBridge 上注册的 YZHJScloseView ⽅法
    ->setMethodType("sign_h5")
    ->execute();

echo "<br>H5签约接⼝：<br>";
var_dump($s2);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";
//
// 获取用户签约状态
$status = new \WGCYunPay\Service\H5SignService($config);
$s3 = $status
    ->setRealName("张三")              # ⽤户真实姓名               （必填）
    ->setIdCard("120114199912121234")   # 证件号码                   （必填）
    ->setMethodType("sign_status")
    ->execute();
echo "<br>获取用户签约状态：<br>";
var_dump($s3);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

// H5对接测试解约接口
//H5对接测试解约接口（注：此接⼝只适⽤于对接联调时⾃助解约使⽤，不影响签约流程，需要按格式提交测试签约信息⽤以配置⽩名单，材料提交详情⻅附录2）
$release = new \WGCYunPay\Service\H5SignService($config);
$s4 = $release
    ->setUid("uid_123")                    # 商户app端⽤户id，不能重复   （必填）
    ->setRealName("张三")              # ⽤户真实姓名               （必填）
    ->setIdCard("120114199912121234")   # 证件号码                   （必填）
    ->setCertificateType(0)       # 证件类型，0-身份证，2港澳居⺠来往内地通⾏证，3-护照，5-台湾居⺠来往⼤陆通⾏证  （必填）
    ->setMethodType("sign_release")
    ->execute();
echo "<br>H5对接测试解约接口：<br>";
var_dump($s4);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

//
////回调+验签
//$notifyJsonData = '{"data":"RDthP6Va2vzHbAjAc5S17BozGK4AxlvWJbWpVdo/GGg1OZR96ga3U+fsB34bIkQtecZIr7yrsfMBL1RXCn+Vri/OjA0dSDFhe+LNhmy+NWw6r6qzyXetBVi0fgCFIg4rk8wrG4ZAzHt3UF2o/Zvm3DX5oHHySvBHuo4Xu5Lm4V2sQFyE8G4Y9HnN+Fe11EqvrPmf2uoPw4NANkLchZNQwwvLHqknqgD3+4gpuf6UXjyhhDvs1Icz6x3Uef001wnUmMDAWwDWfyRWFsiI3NP0KfBlOTj/oGfIGzV32X5uAn8kbXegbuql890muZVD1ik6QEh5Stl78ne3jKvJlzfoMx95N0qrfmlhlTeZ+ouu2/3112gFyVlzwJtMpPAYv/Rt8NB9dy0H05Hgg1bLEGI8HCy71HjKeZY2mvXHmvt38cTWu2gctwukAaJZZjOYMfWq61YmSmewMWsU/UEOZqLaS/QNxC0p319EgQ3WDzj0oYEev0qE2Zmzjes+Hky0DZ5G7giCYpvIJnM+TD/pGLZ+dzqaouu8mo+GKxbRDWi88EJYPxvEmNiSfKKusgAHVsG4QNp0Q21lguqzD2wUcjmzJnKHPCs7zqro4R/oyM+WKUQo4EvBEPZpunGtVsX0AQpOH9ipFa+b6u9O8OZn6zsI177KCnJo5AR73YXcSWGBFaGtrgTNCIsPgujulGNHFKLkBOuAzc40KhEm7JBFiXiX9H1bvb415yedDTFvPTTPVyGkQ+i/Km3L0yr/eCxnMWTKAS1YPijhPg/ybhi+8KfTVHFnlEzvsLmNlhaju7TJyuaOeazL9iNfaT/nCxcUi2hh1ZMW0eI0+iU97IQM6uNNloe3LcTptE6uMqeXha7mR3iPrbIyQgTl2t49n4JTmIcoFAmr1Hb4oM+CWLrhuYN8SM4nPo1G/lKd","mess":"3361023","timestamp":"1594897034","sign":"LKWfx/XQLlribLPkf30PB56trFgangdcS2ypj/Ilmzf0eotJ7ZxNmzxoE0nuKR5OOo99YG+wpF9lDVszzdRZZ1uuKHWXvcXMuDh40bN6tMdLoH5G2wBlTrKWe/3AoG+P/xuVng/HdHHyXDwBynTBLBIrzDYNjrxUHz3U5HAWmvo=","sign_type":"rsa"}';
//$notifyData = json_decode($notifyJsonData, true);
//var_dump($notifyData);
//$datainfo = \WGCYunPay\Service\Des3Service::decode($notifyData['data'], $config->des3_key);
//$result= new \WGCYunPay\Util\RsaUtil($config);
//$verifyResult=$result->verify($notifyData);
//echo"===================================================================================<br>回调解密：<br>";
//var_dump($datainfo);
//
//echo"<br>===================================================================================<br>验签结果：<br>";
//var_dump($verifyResult);
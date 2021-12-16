<?php

require_once __DIR__ . '/bootstrap.php';

//  个体工商注册
// 1）⼯商实名信息录⼊接⼝
//图片转base64
function getuploadfileinfo($file)
{
    //$file = $_REQUEST['user_images']; //web版使用
    if ($fp = fopen($file, "rb", 0)) {
        $gambar = fread($fp, filesize($file));
        fclose($fp);
        //获取图片base64
        $base64 = chunk_split(base64_encode($gambar));
        //echo $base64;
        return  $base64;
    }
}

$realname = new \WGCYunPay\Service\AicService($config);
$a1 = $realname
    ->setUid("uid_2019")                                          # 商户端的⽤户id，在商户系统唯一
    ->setInfoProvider("dealer")                                     # 信息提供⽅  dealer：商户⽅全部提供   union：商户⽅+云账户
    ->setRealName("张喵喵")                                           # 姓名(info_provider为dealer时,必填，info_provider为union时，手机号、姓名和身份证至少有一项必传)
    ->setIdCard("120114199912121234")                                   # 身份证(info_provider为dealer时,必填，info_provider为union时，手机号、姓名和身份证至少有一项必传)
    ->setValidity_start("2001-01-01")                        # 身份证有效期开始时间(info_provider为dealer时,必填)
    ->setValidity_end("2031-01-01")                          # 身份证有效期结束时间(如果为“⻓期”，则传汉字“⻓期”,info_provider为dealer时,必填)
    ->setPhone("+86-18618880001")                                      # 手机号(+86-18618880001格式，区号和⼿机号以-连接，info_provider为dealer时,必填，info_provider为union时，手机号、姓名和身份证至少有一项必传)
    ->setLive(getuploadfileinfo("/Library/WebServer/Documents/test.png"))   # jpg格式⽤户活体照⽚的base64编码字符串，⼩于100k
    ->setMethodType("realname")
    ->execute();

echo "<br>⼯商实名信息录⼊接⼝：<br>";
var_dump($a1);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

//  预启动接⼝
$pre = new \WGCYunPay\Service\AicService($config);
$a2 = $pre
    ->setUid("uid_123")                          # 商户端的⽤户id，在商户系统唯一
    ->setType(1)                                   #客户端类型 1：H5
    ->setInfoProvider("dealer")                   # 信息提供⽅  dealer：商户⽅全部提供   union：商户⽅+云账户  broker：云账户全部提供
    ->setColor("#007AFF")                              #H5⻚⾯主体颜⾊，当前仅⽀持蓝⾊#007AFF
    ->setUrl("http://www.xxx.com")                  #⽤户注册个体⼯商户完成后回调url，post请求
    ->setReturnUrl("http://www.xxx.com")            #跳转url，退出h5⻚⾯时通过此url跳回到商户指定⻚⾯，如果为空，将会执⾏jsBridge上注册的YZHJScloseView⽅法
    ->setMethodType("h5_url")
    ->execute();

echo "<br>预启动接⼝：<br>";
var_dump($a2);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";


// 查询注册状态
$status = new \WGCYunPay\Service\AicService($config);
$a3= $status
    ->setUid("uid_2019")                         # 商户端的⽤户id，在商户系统唯一
    ->setMethodType("aic_status")
    ->execute();
echo "<br>查询注册状态：<br>";
var_dump($a3);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

//
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
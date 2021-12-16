<?php

require_once __DIR__ . '/bootstrap.php';

//  发票接口
// 1）查询商户已开具发票⾦额和待开具发票⾦额
$invoice = new \WGCYunPay\Service\InvoiceService($config);
$i1 = $invoice
    ->setYear(2019)              # 按年份查询已开和待开发票⾦额，不传代表当前年份
    ->setMethodType("invoice-stat")
    ->execute();
var_dump($invoice);
echo "<br>查询商户已开具发票⾦额和待开具发票⾦额：<br>";
var_dump($i1);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

////// 查询综合服务平台商户⽬前可开票额度和开票信息
//$amount = new \WGCYunPay\Service\InvoiceService($config);
//$i2 = $amount
//    ->setMethodType("invoice-amount")
//    ->execute();
//
//echo "<br>查询综合服务平台商户⽬前可开票额度和开票信息：<br>";
//var_dump($i2);
//echo "<br>----------------------------------------------------------------------------------------------------------------<br>";
//
// 开票申请
$apply = new \WGCYunPay\Service\InvoiceService($config);
$i3 = $apply
    ->setInvoiceApplyId("123321")         #  发票申请编号（必填）
    ->setAmount("10.00")                               #  申请开票⾦额（必填）
    ->setInvoiceType("2")                              # 发票类型（必填）1:专票 2:普票
    ->setBankNameAccount("")                           #开户⾏及账号（选填，不填使⽤默认值）
    ->setGoodsServicesName("")                         # 货物或应税劳务、服务名称 (选填，不填使⽤默认值)
    ->setRemark("备注信息")                             # 发票备注 (选填，每张发票备注栏相同)
    ->setMethodType("apply")
    ->execute();
echo "<br>开票申请：<br>";
var_dump($i3);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

//// 查询开票申请状态
//$applystatus = new \WGCYunPay\Service\InvoiceService($config);
//$i4 = $applystatus
//    ->setInvoiceApplyId("123456")            #  发票申请编号（与发票申请号必须⼆选⼀）
//    ->setApplicationId("12345678990")                     #  发票申请号  （与发票申请编号必须⼆选⼀）
//    ->setMethodType("invoice-status")
//    ->execute();
//echo "<br>查询开票申请状态：<br>";
//var_dump($i4);
//echo "<br>----------------------------------------------------------------------------------------------------------------<br>";
//
// 下载pdf
$pdf = new \WGCYunPay\Service\InvoiceService($config);
$i5 = $pdf
    ->setInvoiceApplyId("123456")            #  发票申请编号（与发票申请号必须⼆选⼀）
    ->setApplicationId("12345678990")                     #  发票申请号  （与发票申请编号必须⼆选⼀）
    ->setMethodType("invoice-pdf")
    ->execute();
echo "<br/>下载pdf：<br/>";
var_dump($i5);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

// 私钥解密
//pwd为响应参数中用商户公钥加密后的密文
$pwd="djmHuqlzGF2FVFuN6bTEGRacIcQPhjwDZghMePbBJqHWA8d/DebSl5hUlQEdkIfjwu6H9Rx29BJWdgH8wWtNfOQaa7Kzcfip/OM3iv/KblvmWyPC72fEpL2RRY80PzELI+BRmF1Jj7oxuRYmmQDgqZSm1x7WsJPNuGhL6Iq23Vw=";
$rsa = new \WGCYunPay\Util\RSAUtil($config);
$dd=$rsa->privateDecrypt($pwd);
echo "明文密码:".$dd;
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";


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

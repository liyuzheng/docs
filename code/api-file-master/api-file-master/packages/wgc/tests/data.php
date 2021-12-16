<?php

require_once __DIR__ . '/bootstrap.php';

//数据接口

// 查询日订单文件
$orderFile = new \WGCYunPay\Service\DataFileService($config);

$d1 = $orderFile
    ->setOrderDate("2020-06-01")        #查询时间（不能查询当日）
    ->setMethodType("order")
    ->execute();
echo "<br>查询日订单文件：<br>";
var_dump($d1);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

// 查询订单记录
$order = new \WGCYunPay\Service\DataFileService($config);
$d2 = $order
    ->setOrderDate("2020-08-01")    #订单查询⽇期（必填）
    ->setOffset("0")                   #偏移量，最⼩从0开始 （必填）
    ->setLength("50")                  #每⻚最多返回条数，最⼤为200 （必填）
    ->setchannel("支付宝")             # 银⾏卡，⽀付宝，微信(不填时为银⾏卡订单查询)(选填)
    ->setDataType("encryption")      # 如果为encryption，则对返回的data进行加密(选填)
    ->setMethodType("order-record")
    ->execute();
 echo "<br>查询日订单记录：<br>";
var_dump($d2);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

// 查询日流水文件
$orderFile = new \WGCYunPay\Service\DataFileService($config);

$d3 = $orderFile
    ->setOrderDate("2020-08-01")             #查询时间（不能查询当日）
    ->setMethodType("bill")
    ->execute();

echo "<br>查询日流水文件：<br>";
var_dump($d3);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";



// 充值记录查询
$recharge = new \WGCYunPay\Service\DataFileService($config);
$d4 = $recharge
    ->setAt("2020-08-01","2020-08-10")              #查询起止时间（不能超过30天）
    ->setMethodType("recharge-record")
    ->execute();

echo "<br>查询商户充值记录：<br>";
var_dump($d4);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";


// 查询日订单文件（交易和退款）
$orderFile = new \WGCYunPay\Service\DataFileService($config);

$d5 = $orderFile
    ->setOrderDate("2020-08-01")        #查询时间（不能查询当日）
    ->setMethodType("order-day")
    ->execute();
echo "<br>查询日订单文件（交易和退款）：<br>";
var_dump($d5);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

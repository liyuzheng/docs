<?php

require_once __DIR__ . '/bootstrap.php';

//  个税明细下载

$download = new \WGCYunPay\Service\TaxService($config);
$d1 = $download
    ->setEntId("accumulus_tj")           # 商户签约主体，其中天津：accumulus_tj， 上海：accumulus_sh（选填）
    ->setYearMonth("2020-07")        #   所属期（必填）
    ->execute();
echo "<br/>个税明细下载：<br/>";
var_dump($d1);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

// 私钥解密
//pwd为响应参数中用商户公钥加密后的密文
$pwd="djmHuqlzGF2FVFuN6bTEGRacIcQPhjwDZghMePbBJqHWA8d/DebSl5hUlQEdkIfjwu6H9Rx29BJWdgH8wWtNfOQaa7Kzcfip/OM3iv/KblvmWyPC72fEpL2RRY80PzELI+BRmF1Jj7oxuRYmmQDgqZSm1x7WsJPNuGhL6Iq23Vw=";
$rsa = new \WGCYunPay\Util\RSAUtil($config);
$dd=$rsa->privateDecrypt($pwd);
echo "明文密码:".$dd;
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

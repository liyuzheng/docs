<?php

require_once __DIR__ . '/bootstrap.php';

//用户信息验证
// 银行卡四要素鉴权请求（下发短验）
//$verify = new \WGCYunPay\Service\AuthenticationService($config);
//$v1 = $verify
//    ->setRealName("张喵喵")                # 银⾏开户姓名 （必填）
//    ->setCardNo("6214830088886666")        # 银行卡号     （必填）
//    ->setIdCard("120110198808086666")      # 身份证       （必填）
//    ->setMobile("18600001111")             # 银行预留手机号 （必填）
//    ->setMethodType("verfy_request")
//    ->execute();
//var_dump($v1);
//echo "<br>----------------------------------------------------------------------------------------------------------------<br>";
//
//// 银行卡四要素鉴权确认（上送短验）
//$verify = new \WGCYunPay\Service\AuthenticationService($config);
//$v2 = $verify
//    ->setRealName("张喵喵")                 # 银⾏开户姓名 （必填）
//    ->setCardNo("6214830088886666")         # 银行卡号     （必填）
//    ->setIdCard("120110198808086666")       # 身份证       （必填）
//    ->setMobile("18600001111")              # 银行预留手机号 （必填）
//    ->setCaptcha("123456")                 # 短信验证码    （必填）
//    ->setRef("rx0g4iiLWoWA==")                #交易凭证，鉴权请求接口返回  （必填）
//    ->setMethodType("verify_confirm")
//    ->execute();
//var_dump($v2);
//echo "<br>----------------------------------------------------------------------------------------------------------------<br>";
//
//// 银行卡四要素验证
//$verify = new \WGCYunPay\Service\AuthenticationService($config);
//$v3 = $verify
//    ->setRealName("张喵喵")                 # 银⾏开户姓名 （必填）
//    ->setCardNo("6214830088886666")         # 银行卡号     （必填）
//    ->setIdCard("120110198808086666")       # 身份证       （必填）
//    ->setMobile("18600001111")              # 银行预留手机号 （必填）
//    ->setMethodType("verify_four")
//    ->execute();
//var_dump($v3);
//echo "<br>----------------------------------------------------------------------------------------------------------------<br>";
//
//// 银行卡三要素验证
//$verify = new \WGCYunPay\Service\AuthenticationService($config);
//$v4 = $verify
//    ->setRealName("张喵喵")                     # 银⾏开户姓名 （必填）
//    ->setCardNo("6214830088886666")             # 银行卡号     （必填）
//    ->setIdCard("120110198808086666")           # 身份证       （必填）
//    ->setMethodType("verify_three")
//    ->execute();
//var_dump($v4);
//echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

// 身份证实名验证
$verify = new \WGCYunPay\Service\AuthenticationService($config);
$v5 = $verify
    ->setRealName("张喵喵")                        # 银⾏开户姓名 （必填）
    ->setIdCard("120110198808086666")              # 身份证       （必填）
    ->setMethodType("verify_id")
    ->execute();
var_dump($v5);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";
//
////图片转base64
//function getuploadfileinfo()
//{
//    $file = "/Library/WebServer/Documents/yun-rsa/images/2.png";
//    if ($fp = fopen($file, "rb", 0)) {
//        $gambar = fread($fp, filesize($file));
//        fclose($fp);
//
//        //获取图片base64
//        $base64 = chunk_split(base64_encode($gambar));
//        return  [$base64];
//        // 输出
//        //  $encode = '<img src="data:image/jpg/png/gif;base64,' . $base64 . '" >';
//
//    }
//}
//
//// 上传免验证用户信息
//$verify = new \WGCYunPay\Service\AuthenticationService($config);
//$v6 = $verify
//    ->setRealName("张三")                          #姓名（必填）
//    ->setIdCard("EK3323")                           # 护照、港澳台居⺠居住证等证   （必填）
//    ->setCardType("passport")                      #证件类型码，见文档   （必填）
//    ->setCommentApply("备注")                   #备注       （选填）
//    ->setUserImages( getuploadfileinfo())  # 人员信息图片,填写图片路径   （必填）
//    ->setCountry("CHN")                            #国家代码，见文档附录   （必填  ）
//    ->setBirthday("20190909")                     #出生日期        （必填）
//    ->setGender("男")                              #性别           （必填）
//    ->setNotifyUrl("http://www.xxx.com")         #回调地址         （选填）
//    ->setRef("12345qwer")                            # 唯⼀流⽔号，回调时会附带   （必填）
//    ->setMethodType("user_exempted_info")
//    ->execute();
//echo "<br>上传免验证用户信息：<br>";
//
//var_dump($v6);
//echo "<br>----------------------------------------------------------------------------------------------------------------<br>";
//
//// 查看用户免验证名单是否存在
//
//$verify = new \WGCYunPay\Service\AuthenticationService($config);
//$v7 = $verify
//    ->setRealName("张三")                 #姓名   （必填）
//    ->setIdCard("EK3323")                  #证件号  （必填）
//    ->setMethodType("user_white_check")
//    ->execute();
//echo "<br>查看用户免验证名单是否存在：<br>";
//
//var_dump($v7);
//echo "<br>----------------------------------------------------------------------------------------------------------------<br>";

// 银行卡信息查询

$verify = new \WGCYunPay\Service\AuthenticationService($config);
$v8 = $verify
    ->setBankName("招商银行")               # 银行名称（卡号、银⾏名参数⼆选⼀，都有时优先匹配卡号）
    ->setCardNo("6214838866669999")          # 卡号   （卡号、银⾏名参数⼆选⼀，都有时优先匹配卡号））
    ->setMethodType("bank_info")
    ->execute();
echo "<br>银行卡信息查询：<br>";

var_dump($v8);
echo "<br>----------------------------------------------------------------------------------------------------------------<br>";
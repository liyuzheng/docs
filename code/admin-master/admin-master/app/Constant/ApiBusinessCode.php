<?php


namespace App\Constant;


class ApiBusinessCode
{
    const REQ_PARAMETER_ERROR              = 996; //请求签名参数异常
    const REQ_SIGN_REPEAT                  = 997; //请求签名重复
    const REQ_SIGN_ERROR                   = 998; //请求签名错误
    const FORCED_TO_UPDATE                 = 999; //强制更新
    const AUTH_TOKEN_MISSING               = 1000;//token不存在
    const AUTH_TOKEN_ERROR                 = 1001;//token错误
    const FILE_EXISTS_FALSE                = 1002;//文件不存在
    const REQUEST_PARAMETERS_VERIFY_FAILED = 1003;//参数校验失败
    const SERVICE_UNKNOWN_FORBID           = 1004;//服务端未知错误
    const SERVICE_UPDATE_DB_ERROR          = 1005;//修改数据库失败
    const NOT_FOUND                        = 1006;//数据无法获取
    const SUCCESS                          = 1007;//请求成功
    const LACK_OF_BALANCE                  = 1008;//余额不足
    const FORBID_COMMON                    = 1009;//禁止的动作
    const USER_NOT_FOUND                   = 1010;//找不到用户
    const REQUEST_PARAMETER_ERROR          = 1012;//请求参数错误
    const REQUEST_PARAMETER_MISSING        = 1013;//请求参数缺失
    const PIC_COMPARE_FAIL                 = 1014;//请求参数缺失
    const GOODS_FAILURE                    = 1015;//商品失效
    const HAVE_UNLOCKED                    = 1016;//商品失效
    const BLACKLIST_LOCK                   = 1017;//拉黑
    const GUIDE_RECHARGE                   = 1018;//引导充值
    const NOT_REGISTER                     = 1019;//用户未注册登陆
    const FOLLOW_OFFICE_FAILED             = 1020;//关注公众号失败


    const SMS_CODE_ERROR   = 2001;//短信验证码错误
    const SMS_LOGIN_FAILED = 2002;//一键登录失败

    const USER_BLOCK = 3001; // 用户被其他用户拉黑
}

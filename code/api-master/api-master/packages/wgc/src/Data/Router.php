<?php

namespace WGCYunPay\Data;

class Router
{

    //个体工商注册接口serverroot
    const AIC_SERVICE_URL = 'https://api-aic.yunzhanghu.com';
    //其他接口serverroot
     const SERVICE_URL = 'https://api-jiesuan.yunzhanghu.com';

    //+----------------------------------
    //|  打款接⼝
    //+----------------------------------
    /**
     * 支付提交地址
     */
    const BANK_CARD = 'api/payment/v1/order-realtime';
    const ALI_PAY   = 'api/payment/v1/order-alipay';
    const WX_PAY    = 'api/payment/v1/order-wxpay';

    /**
     * 订单查询
     */
    const QUERY_REALTIME_ORDER = 'api/payment/v1/query-realtime-order';

    /**
     * 余额查询
     */
    const QUERY_ACCOUNTS       = 'api/payment/v1/query-accounts';

    /**
     * 电子回单
     */
    const RECEIPT_FILE         = 'api/payment/v1/receipt/file';

    /**
     * 取消待打款订单
     */
    const ORDER_FAIL           = 'api/payment/v1/order/fail';



    //+----------------------------------
    //| 数据接⼝
    //+----------------------------------

    /**
     * 查询⽇订单⽂件
     */
    const ORDER_DOWNLOAD  = 'api/dataservice/v1/order/downloadurl';

    /**
     * 查询⽇流⽔⽂
     */
    const BILL_DOWNLOAD   = 'api/dataservice/v2/bill/downloadurl';

    /**
     * 查询商户充值记录
     */
    const RECHARGE_RECORD = 'api/dataservice/v2/recharge-record';

    /**
     * 查询日订单数据
     */
    const ORDER_RECORD  = 'api/dataservice/v1/orders';
    /**
     * 查询⽇订单⽂件 (打款和退款订单)
     */
    const ORDER_DAY  = 'api/dataservice/v1/order/day/url';


    //+----------------------------------
    //|  ⽤户信息验证接⼝
    //+----------------------------------
    /**
     * 银⾏卡四要素请求鉴权（下发短信验证码）
     */
    const VERIFY_REQUEST  = 'authentication/verify-request';

    /**
     * 银⾏卡四要素确认鉴权（上传短信验证码）
     */
    const VERIFY_CONFIRM  = 'authentication/verify-confirm';

    /**
     * 银⾏卡四要素验证
     */
    const VERIFY_BANKCARD_FOUR_FACTOR  = 'authentication/verify-bankcard-four-factor';

    /**
     * 银⾏卡三要素验证
     */
    const VERIFY_BANKCARD_THREE_FACTOR = 'authentication/verify-bankcard-three-factor';

    /**
     * 身份证实名验证
     */
    const VERIFY_ID                    = 'authentication/verify-id';

    /**
     * 上传用户免验证名单信息
     */
    const WHITE_INFO_UPLOAD          = 'api/payment/v1/user/exempted/info';
    /**
     * 查看⽤户⽩名单是否存在
     */
    const USER_WHITE_CHECK           = 'api/payment/v1/user/white/check';
    /**
     * 银行卡信息查询
     */
    const BANK_INFO                   = 'api/payment/v1/card';

    //+----------------------------------
    //|  发票接⼝
    //+----------------------------------
    /**
     * 查询商户已开具发票⾦额和待开具发票⾦额
     */
    const INVOICE_STAT       = 'api/payment/v1/invoice-stat';
    /**
     * 查询可开票额度
     */
    const INVOICE_AMOUNT     = 'api/invoice/v2/invoice-amount';
    /**
     * 开票申请
     */
    const INVOICE_APPLY     = 'api/invoice/v2/apply';
    /**
     * 查询开票申请状态
     */
    const INVOICE_APPLY_STATUS     = 'api/invoice/v2/invoice/invoice-status';
    /**
     * 下载发票PDF
     */
    const INVOICE_PDF    = 'api/invoice/v2/invoice/invoice-pdf';

    //+----------------------------------
    //|  个税扣缴明细表下载接口
    //+----------------------------------
    /**
     * 下载个税扣缴明细表
     */
    const TAX_DOWNLOAD    = 'api/tax/v1/taxfile/download';

    //+----------------------------------
    //|  ⽤户签约接⼝
    //+----------------------------------


    /**
     * 获取⽤户签约状态
     */
    const SIGN_USER_STATUS = 'api/sdk/v1/sign/user/status';
    /**
     * H5预申请签约
     */
    const SIGN_PRESIGN_H5   = 'api/sdk/v1/presign';
    /**
     * h5签约接口
     */
    const SIGN_USER_H5 = 'api/sdk/v1/sign/h5';
    
    /**
     * h5测试解约
     */
    const SIGN_RELEASE_H5= 'api/sdk/v1/sign/release';


    //+----------------------------------
    //|  个体工商注册
    //+----------------------------------


    /**
     * ⼯商实名信息录⼊接⼝
     */
    const AIC_REALNAME = 'api/yzh/aic/realname';
    /**
     * 预启动
     */
    const AIC_H5URL   = 'api/yzh/aic/h5url';
    /**
     * 查询个体工商户注册状态
     */
    const AIC_STATUS = 'api/yzh/aic/status';


    public static function getRouter(string $route = ''): string
    {

        if (strpos($route,'AIC') !== false)
            return self:: AIC_SERVICE_URL . '/' . $route;
        else
            return self::SERVICE_URL . '/' . $route;
    }


}

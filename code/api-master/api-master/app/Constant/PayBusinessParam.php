<?php


namespace App\Constant;


use App\Models\TradePay;

class PayBusinessParam
{
    const  APPLE_VERIFY_URL         = 'https://buy.itunes.apple.com/verifyReceipt';
    const  APPLE_SANDBOX_VERIFY_URL = "https://sandbox.itunes.apple.com/verifyReceipt";
    const  APPLE_BUNDLE_ID          = ['com.box.xiaoquan.app'];
    const  APPLE_PASSWORDS          = ['e7988adc64434838ba92298195294e58', '2e050839aa514e5da6e16828a2520d47'];

    const APPLE_ORDER_STATUS_CREATE  = 0;
    const APPLE_ORDER_STATUS_SUCCESS = 1;
    const APPLE_ORDER_STATUS_FAILED  = 2;

    const APPLE_VALIDATION_RECEIPT             = 0; // 验证苹果收据 receipt 字段数据
    const APPLE_VALIDATION_LATEST_RECEIPT_INFO = 1; // 验证苹果收据 latest_receipt_info 字段数据

    const TRADE_PAY_APPLE_ORDER_STATUS_MAPPING = [
        self::APPLE_ORDER_STATUS_CREATE  => TradePay::STATUS_DEFAULT,
        self::APPLE_ORDER_STATUS_SUCCESS => TradePay::STATUS_SUCCESS,
        self::APPLE_ORDER_STATUS_FAILED  => TradePay::STATUS_FAILED,
    ];
}

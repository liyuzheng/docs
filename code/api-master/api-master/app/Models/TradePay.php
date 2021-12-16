<?php


namespace App\Models;


class TradePay extends TradeModel
{
    protected $table = 'trade_pay';
    protected $fillable = [
        'user_id',
        'related_type',
        'related_id',
        'status',
        'channel',
        'os',
        'good_id',
        'ori_amount',
        'discount',
        'amount',
        'currency',
        'order_no',
        'trade_no',
        'done_at',
        'data_id',
        'channel_id',
    ];

    protected $hidden = ['related_type', 'related_id', 'created_at', 'updated_at', 'deleted_at'];
    protected $appends = ['event_at'];

    const RELATED_TYPE_RECHARGE     = 100; // 充值
    const RELATED_TYPE_RECHARGE_VIP = 200; // 充值vip

    const CHANNEL_APPLE   = 100; // 支付渠道 apple
    const CHANNEL_PING_XX = 200; // 支付渠道 ping++
    const CHANNEL_GOOGLE  = 300; // 支付渠道 google

    const OS_IOS            = 100;          // 客户端系统 ios
    const OS_ANDROID        = 200;          // 客户端系统安卓
    const OS_WEB            = 300;          // web客户端例如: 公众号充值
    const OS_NATIVE_WEB     = 400;          // APP内web充值
    const OS_STR_IOS        = 'ios';        // ios 字符串类型
    const OS_STR_ANDROID    = 'android';    // 安卓字符串类型
    const OS_STR_WEB        = 'web';        // web 字符串类型
    const OS_STR_NATIVE_WEB = 'native_web'; // App内web字符串类型

    const STATUS_DEFAULT = 0;   // 订单状态 默认：未支付
    const STATUS_SUCCESS = 100; // 订单状态成功
    const STATUS_FAILED  = 200; // 订单状态支付失败

    // 与流水主表 trade_balance related_type | trade related_type 映射表
    const TRADE_RELATED_TYPES = [
        self::RELATED_TYPE_RECHARGE     => TradeBalance::RELATED_TYPE_RECHARGE,
        self::RELATED_TYPE_RECHARGE_VIP => Trade::RELATED_TYPE_RECHARGE_VIP,
    ];

    const OS_MAPPING = [
        self::OS_STR_IOS        => self::OS_IOS,
        self::OS_STR_ANDROID    => self::OS_ANDROID,
        self::OS_STR_WEB        => self::OS_WEB,
        self::OS_STR_NATIVE_WEB => self::OS_NATIVE_WEB,
        self::OS_IOS            => self::OS_STR_IOS,
        self::OS_ANDROID        => self::OS_STR_ANDROID,
        self::OS_WEB            => self::OS_STR_WEB,
        self::OS_NATIVE_WEB     => self::OS_STR_NATIVE_WEB,
    ];

    public function getEventAtAttribute()
    {
        return substr(date('Y/m/d H:i', $this->created_at->timestamp), 2);
    }
}

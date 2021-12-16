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

    const OS_IOS     = 100; // 客户端系统 ios
    const OS_ANDROID = 200; // 客户端系统安卓

    const STATUS_DEFAULT = 0;   // 订单状态 默认：未支付
    const STATUS_SUCCESS = 100; // 订单状态成功
    const STATUS_FAILED  = 200; // 订单状态支付失败

    // 与流水主表 trade_balance related_type | trade related_type 映射表
    const TRADE_RELATED_TYPES = [
        self::RELATED_TYPE_RECHARGE     => TradeBalance::RELATED_TYPE_RECHARGE,
        self::RELATED_TYPE_RECHARGE_VIP => Trade::RELATED_TYPE_RECHARGE_VIP,
    ];

    public function getEventAtAttribute()
    {
        return substr(date('Y/m/d H:i', $this->created_at->timestamp), 2);
    }
}

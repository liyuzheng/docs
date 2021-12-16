<?php


namespace App\Models;


class TradeBalance extends TradeModel
{
    protected $table = 'trade_balance';
    protected $fillable = ['user_id', 'related_type', 'related_id', 'amount', 'done_at'];

    const RELATED_TYPE_BUY_PRIVATE_CHAT = 100; // 购买私信
    const RELATED_TYPE_BUY_WECHAT       = 101; // 购买微信
    const RELATED_TYPE_BUY_PHOTO        = 102; // 购买红包相册
    const RELATED_TYPE_RECHARGE         = 200; // 充值
    const RELATED_TYPE_RECHARGE_VIP     = 300; // 代币购买VIP
    const RELATED_TYPE_REFUND           = 400; // 解锁私信退款

    // 与流水主表 trade related_type 映射表
    const TRADE_RELATED_TYPES = [
        self::RELATED_TYPE_BUY_PRIVATE_CHAT => Trade::RELATED_TYPE_BUY_PRIVATE_CHAT,
        self::RELATED_TYPE_BUY_WECHAT       => Trade::RELATED_TYPE_BUY_WECHAT,
        self::RELATED_TYPE_RECHARGE         => Trade::RELATED_TYPE_RECHARGE,
        self::RELATED_TYPE_BUY_PHOTO        => Trade::RELATED_TYPE_RED_PACKET_USER_PHOTO,
        self::RELATED_TYPE_RECHARGE_VIP     => Trade::RELATED_TYPE_CURRENCY_RECHARGE_VIP,
        self::RELATED_TYPE_REFUND           => Trade::RELATED_TYPE_REFUND,
    ];
}

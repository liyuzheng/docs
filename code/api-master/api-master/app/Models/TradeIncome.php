<?php


namespace App\Models;


class TradeIncome extends TradeModel
{
    protected $table = 'trade_income';
    protected $fillable = ['user_id', 'related_type', 'related_id', 'amount', 'done_at'];

    const RELATED_TYPE_BUY_PRIVATE_CHAT_DIVIDE = 100; // 购买私信分成
    const RELATED_TYPE_BUY_WECHAT_DIVIDE       = 101; // 购买微信分成
    const RELATED_TYPE_RED_PACKET_USER_PHOTO   = 102; // 红包解锁相册分成
    const RELATED_TYPE_WITHDRAW                = 200; // 提现
    const RELATED_TYPE_INVITE_USER             = 300; // 邀请注册

    // 与流水主表 related_type 映射表
    const TRADE_RELATED_TYPES = [
        self::RELATED_TYPE_BUY_PRIVATE_CHAT_DIVIDE => Trade::RELATED_TYPE_BUY_PRIVATE_CHAT_DIVIDE,
        self::RELATED_TYPE_BUY_WECHAT_DIVIDE       => Trade::RELATED_TYPE_BUY_WECHAT_DIVIDE,
        self::RELATED_TYPE_RED_PACKET_USER_PHOTO   => Trade::RELATED_TYPE_BUY_PHOTO_DIVIDE,
        self::RELATED_TYPE_WITHDRAW                => Trade::RELATED_TYPE_WITHDRAW,
        self::RELATED_TYPE_INVITE_USER             => Trade::RELATED_TYPE_INVITE,
    ];
}

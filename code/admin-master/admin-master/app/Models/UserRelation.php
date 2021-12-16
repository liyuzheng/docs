<?php


namespace App\Models;


class UserRelation extends BaseModel
{
    protected $table    = 'user_relation';
    protected $fillable = ['type', 'user_id', 'target_user_id', 'expired_at'];

    const TYPE_PRIVATE_CHAT = 100; // 私聊
    const TYPE_LOOK_WECHAT  = 200; // 查看微信

    const TYPE_MAPPING = [
        'private_chat' => self::TYPE_PRIVATE_CHAT,
        'wechat'       => self::TYPE_LOOK_WECHAT,
    ];

    // 解锁类型价格映射表
    const TYPE_PRICES = [
        self::TYPE_PRIVATE_CHAT => Config::KEY_UNLOCK_PRIVATE_CHAT_PRICE,
        self::TYPE_LOOK_WECHAT  => Config::KEY_UNLOCK_WECHAT_PRICE,
    ];

    // trade_buy related_id 映射表
    const TRADE_BUY_RELATED_TYPES = [
        self::TYPE_PRIVATE_CHAT => TradeBuy::RELATED_TYPE_BUY_PRIVATE_CHAT,
        self::TYPE_LOOK_WECHAT  => TradeBuy::RELATED_TYPE_BUY_WECHAT,
    ];

    const RELATIONS_MAPPING = [
        self::TYPE_PRIVATE_CHAT => '私聊',
        self::TYPE_LOOK_WECHAT  => '微信',
    ];

    // 默认vip可免费解锁用户数
    const VIP_FREE_UNLOCK_USER_COUNT = 10;
}

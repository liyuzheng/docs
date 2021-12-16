<?php

namespace App\Models;

class Trade extends BaseModel
{
    protected $table = 'trade';
    protected $fillable = ['user_id', 'related_type', 'related_id', 'amount', 'done_at'];

    const RELATED_TYPE_BUY_PRIVATE_CHAT        = 100; // 购买私信
    const RELATED_TYPE_BUY_PRIVATE_CHAT_DIVIDE = 101; // 购买私聊分成
    const RELATED_TYPE_RED_PACKET_USER_PHOTO   = 102; // 红包解锁相册
    const RELATED_TYPE_BUY_WECHAT              = 200; // 购买微信
    const RELATED_TYPE_BUY_WECHAT_DIVIDE       = 201; // 购买微信分成
    const RELATED_TYPE_BUY_PHOTO_DIVIDE        = 201; // 购买红包分成
    const RELATED_TYPE_RECHARGE                = 300; // 充值
    const RELATED_TYPE_RECHARGE_VIP            = 301; // 充值vip
    const RELATED_TYPE_CURRENCY_RECHARGE_VIP   = 302; // 代币充值vip
    const RELATED_TYPE_WITHDRAW                = 400; // 提现
    const RELATED_TYPE_INVITE                  = 500; // 邀请收益
    const RELATED_TYPE_REFUND                  = 600; // 余额退款
}

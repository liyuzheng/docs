<?php


namespace App\Constant;


class NeteaseCustomCode
{
    const UNLOCK_USER_MESSAGE = 1;  // 解锁用户消息类型
    const CHARM_GIRL_AUTH     = 2;  // 魅力女生认证
    const MOMENT_LIKE         = 3;  // 动态点赞
    const TRADE_PAY_DONE      = 4;  // 充值交易完成
    const STRONG_REMINDER     = 5;  // 客户端强提醒
    const FOLLOW_OS_STATE     = 6;  // 认证信公众号状态
    const ALERT_IMAGE_MESSAGE = 7;  // 带圆形图片的弹框 如: 私聊解锁退款
    const BE_FOLLOW           = 8;  // 被关注收到的系统消息
    const USER_VISITED        = 9;  // 谁看过我消息
    const PAGE_OPEN_NOTICE    = 10; // 打开页面或弹框消息
    const KEFU_ZHICHI         = 11; // 用户离线发送客服
    const CURRENCY_MENTION    = 12; // 通用强提醒弹
    const CHAT_TIPS           = 13; // 私聊的小提示
    const DELETE_LOGIN        = 14; // 踢登消息
}

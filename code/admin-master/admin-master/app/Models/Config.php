<?php


namespace App\Models;


class Config extends BaseModel
{
    protected $table = 'config';
    protected $fillable = ['appname','key','value','type','show_type','desc'];

    const TYPE_STRING = 100;//字符串
    const TYPE_JSON   = 200;//json

    const TYPE_ARR = [
        100 => '字符串',
        200 => 'json',
    ];

    const SHOW_TYPE_UNIVERSAL = 100;//通用
    const SHOW_TYPE_BACK      = 200;//后端专用


    const SHOW_TYPE__ARR = [
        100 => '通用',
        200 => '后端专用',
    ];

    const COMMON_APP_NAME = 'common';

    const KEY_UNLOCK_PRIVATE_CHAT_PRICE = 'private_chat_price';  // 解锁私聊价格key
    const KEY_UNLOCK_WECHAT_PRICE       = 'unlock_wechat_price'; // 解锁微信价格 key


    const KEY_ANDROID_LATEST_URL = 'android_latest_url';//安卓最新价格
    const KEY_APPLE_LATEST_URL   = 'apple_latest_url';//苹果最新价格

    const KEY_NETEASE_KF = 'netease_kf';
}

<?php


namespace App\Models;


class SwitchModel extends BaseModel
{
    protected $table = 'switch';

    const DEFAULT_STATUS_CLOSE = 0;//关闭
    const DEFAULT_STATUS_OPEN  = 1;//开启

    const KEY_LOCK_WECHAT         = 'lock_wechat';
    const KEY_LOCK_PHONE          = 'phone';
    const KEY_LOCK_STEALTH        = 'stealth';
    const KEY_ADMIN_HIDE_USER     = 'admin_hide_user';
    const KEY_PUSH_TEM_MSG        = 'push_tem_msg';//是否推送模板消息
    const KEY_CLOSE_WE_CHAT_TRADE = 'close_wx_trade';
}

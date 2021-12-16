<?php


namespace App\Models;


use function MongoDB\select_server;

class Option extends BaseModel
{
    protected $table    = 'option';
    protected $fillable = ['p_id', 'name', 'url', 'type', 'code'];

    const TYPE_FRONT = 100;
    const TYPE_BACK  = 200;
    const TYPE_CUSTOMER_SERVICE_SCRIPT_CHARM_GIRL = 300;
    const TYPE_CUSTOMER_SERVICE_SCRIPT_REPORTED = 301;
    const TYPE_CUSTOMER_SERVICE_SCRIPT_REPORT = 302;
    const TYPE_CUSTOMER_SERVICE_SCRIPT_CHARM_GIRL_DENY = 303;
    const TYPE_CUSTOMER_SERVICE_SCRIPT_WECHAT_UPDATE_DENY = 304;
    const TYPE_CUSTOMER_SERVICE_SCRIPT_STRONG_REMINDER = 305;

    const TYPE_CONTENT = [
        self::TYPE_CUSTOMER_SERVICE_SCRIPT_CHARM_GIRL=>'魅力女生审核列表可添加快捷语句修改',
        self::TYPE_CUSTOMER_SERVICE_SCRIPT_REPORTED =>'被举报列表（未处理）—处理—被投诉用户处理',
        self::TYPE_CUSTOMER_SERVICE_SCRIPT_REPORT => '被举报列表（未处理）—处理—投诉用户处理',
        self::TYPE_CUSTOMER_SERVICE_SCRIPT_CHARM_GIRL_DENY =>'魅力女生资料修改审核列表—拒绝',
        self::TYPE_CUSTOMER_SERVICE_SCRIPT_WECHAT_UPDATE_DENY=>'微信修改审核列表—拒绝',
        self::TYPE_CUSTOMER_SERVICE_SCRIPT_STRONG_REMINDER=>'强提醒—新增'
    ];
}

<?php


namespace App\Models;


class Sms extends BaseModel
{
    protected $table    = 'sms';
    protected $fillable = [
        'type',
        'user_id',
        'area',
        'mobile',
        'email',
        'code',
        'client_id',
        'token',
        'expired_at',
        'used_at',
        'client_ip'
    ];

    const TYPE_MOBILE_SMS            = 100; // 短信登录
    const TYPE_MOBILE_QUICKLY        = 200; // 一键登录
    const TYPE_NOT_ACTIVE_CHARM_GIRL = 300; // 魅力女生不活跃短信
    const TYPE_INVITE_BIND           = 400; // 邀请绑定登录
    const TYPE_RESET_PASSWORD        = 500; // 重置密码

    const TYPE_STR_MAPPING = [
        'login'       => self::TYPE_MOBILE_SMS,
        'password'    => self::TYPE_RESET_PASSWORD,
        'invite_bind' => self::TYPE_INVITE_BIND,
    ];
}

<?php


namespace App\Models;


class InviteRecord extends BaseModel
{
    protected $table = 'invite_record';
    protected $fillable = [
        'channel',
        'type',
        'user_id',
        'target_user_id',
        'status',
        'done_at'
    ];

    const TYPE_USER_REG    = 100;
    const TYPE_USER_MEMBER = 101;

    const STATUS_DEFAULT = 0;
    const STATUS_SUCCEED = 100;

    const CHANNEL_APP        = 100; // app邀请途径
    const CHANNEL_APPLET     = 200; // 小程序邀请途径
    const CHANNEL_STR_APP    = 'app';
    const CHANNEL_STR_APPLET = 'applet';

    // 邀请途径映射
    const CHANNEL_MAPPING = [
        self::CHANNEL_STR_APP    => self::CHANNEL_APP,
        self::CHANNEL_STR_APPLET => self::CHANNEL_APPLET,
        self::CHANNEL_APP        => self::CHANNEL_STR_APP,
        self::CHANNEL_APPLET     => self::CHANNEL_STR_APPLET,
    ];
}

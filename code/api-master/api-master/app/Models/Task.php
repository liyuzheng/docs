<?php


namespace App\Models;


class Task extends BaseModel
{
    protected $table = 'task';
    protected $fillable = [
        'related_type',
        'related_id',
        'user_id',
        'status',
        'done_at'
    ];

    const RELATED_TYPE_MAN_INVITE_REG      = 100;
    const RELATED_TYPE_MAN_INVITE_MEMBER   = 101;
    const RELATED_TYPE_WOMAN_INVITE_MEMBER = 102;

    const RELATED_TYPE_APPLET_INVITE_MEMBER = 103; // 小程序邀请来的用户充值会员任务
    const RELATED_TYPE_MEMBER_DISCOUNT      = 104; // 充值会员折扣任务

    const STATUS_DEFAULT = 0;
    const STATUS_SUCCEED = 100;
}

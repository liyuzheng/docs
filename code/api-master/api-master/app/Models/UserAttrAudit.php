<?php


namespace App\Models;


class UserAttrAudit extends BaseModel
{
    protected $table    = 'user_attr_audit';
    protected $fillable = [
        'user_id',
        'key',
        'value',
        'check_status',
        'done_at'
    ];

    const STATUS_DELAY     = 0; //审核中
    const STATUS_FAIL      = 200;//审核未通过
    const STATUS_PASS      = 100;//审核通过
    const STATUS_ANTI_PASS = 101;

    const KEY_ARR = [
        'nickname' => '昵称',
        'intro'    => '个人简介'
    ];
}

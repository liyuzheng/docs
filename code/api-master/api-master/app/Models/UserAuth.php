<?php


namespace App\Models;


class UserAuth extends BaseModel
{
    protected $table    = 'user_auth';
    protected $fillable = [
        'user_id',
        'type',
        'secret',
    ];
    //云信id
    const TYPE_NETEASE_TOKEN = 100;
    //用户密码
    const TYPE_PASSWORD      = 200;
    //公众号openid
    const TYPE_OFFICE_OPENID = 300;
}

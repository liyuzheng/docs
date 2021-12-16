<?php


namespace App\Models;


class StatRemainLoginLog extends BaseModel
{
    protected $table    = 'stat_remain_login_log';
    protected $fillable = [
        'user_id',
        'os',
        'login_at',
        'remain_day',
        'register_at'
    ];

    const OS_UNKNOWN = 0;
    const OS_IOS     = 100;
    const OS_ANDROID = 200;
}

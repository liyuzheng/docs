<?php


namespace App\Models;


class UserSwitch extends BaseModel
{
    protected $table    = 'user_switch';
    protected $fillable = ['uuid', 'user_id', 'switch_id', 'status'];

    const STATUS_CLOSE      = 0;//关闭
    const STATUS_OPEN       = 1;//开启
    const STATUS_ADMIN_LOCK = 2;//后台锁
}

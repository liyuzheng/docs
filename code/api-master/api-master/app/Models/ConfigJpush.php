<?php


namespace App\Models;


class ConfigJpush extends BaseModel
{
    protected $table    = 'config_jpush';
    protected $fillable = [
        'os',
        'is_push',
        'appname',
        'key',
        'secret',
        'duration',
        'private_key',
        'status'
    ];

    const OS_IOS     = 100;
    const OS_ANDROID = 200;

    const IS_PUSH_TRUE  = 1;
    const IS_PUSH_FALSE = 0;

    const STATUS_DEFAULT = 0;
    const STATUS_SUCCEED = 100;
}

<?php


namespace App\Models;


class UserLookOver extends BaseModel
{
    protected $table    = 'user_look_over';
    protected $fillable = [
        'user_id',
        'target_id',
        'resource_id',
        'expired_at'
    ];

    const STATUS_NOT_KNOW = 0;
    const STATUS_NOT_READ = 100;
    const STATUS_READED   = 101;

    const STATUS_IMAGE_DURATION           = 3;
    const STATUS_AUTH_USER_IMAGE_DURATION = 10;
}

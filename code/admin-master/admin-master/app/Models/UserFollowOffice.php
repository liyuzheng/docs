<?php


namespace App\Models;


class UserFollowOffice extends BaseModel
{
    protected $table    = 'user_follow_office';
    protected $fillable = [
        'user_id',
        'ticket',
        'data',
        'url',
        'status',
        'expired_at'
    ];

    const STATUS_DEFAULT       = 0;
    const STATUS_FOLLOW        = 100;
    const STATUS_CANCEL_FOLLOW = 200;

    const BIZ_TYPE_FOLLOW_BIND = 1;
}

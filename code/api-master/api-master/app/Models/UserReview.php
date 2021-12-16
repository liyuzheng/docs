<?php


namespace App\Models;


class UserReview extends BaseModel
{
    protected $table    = 'user_review';
    protected $fillable = [
        'user_id',
        'nickname',
        'birthday',
        'region',
        'height',
        'weight',
        'job',
        'intro',
        'check_status',
        'reason',
        'alert_status',
        'done_at'
    ];

    const CHECK_STATUS_DELAY              = 0;
    const CHECK_STATUS_BLACK_DELAY        = 1;
    const CHECK_STATUS_FOLLOW_WECHAT      = 2;
    const CHECK_STATUS_FOLLOW_WECHAT_FACE = 3;
    const CHECK_STATUS_FAIL               = 200;
    const CHECK_STATUS_PASS               = 100;
    const CHECK_STATUS_IGNORE             = 300;
    const CHECK_STATUS_BLACK_IGNORE       = 301;

    const ALERT_STATUS_ACTIVE  = 1;
    const ALERT_STATUS_PASSIVE = 0;

    public function userPhotos()
    {
        return $this->hasMany(Resource::class, 'related_id', 'user_id');
    }
}

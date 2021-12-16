<?php


namespace App\Models;


class SmsAd extends BaseModel
{
    protected $table    = 'sms_ad';
    protected $fillable = [
        'biz_key',
        'mobile',
        'send_at',
        'status'
    ];

    const STATUS_SUCCESS = 100;
    const STATUS_FAIL    = 200;
}

<?php


namespace App\Models;


class Wechat extends BaseModel
{
    protected $table    = 'wechat';
    protected $fillable = [
        'user_id',
        'wechat',
        'qr_code',
        'parse_content',
        'check_status',
        'done_at'
    ];

    const STATUS_DELAY  = 0;//未进行审核
    const STATUS_FAIL   = 200;//未通过审核
    const STATUS_PASS   = 100;//通过审核
    const STATUS_IGNORE = 300;//忽略

    const STATUS = [
        0   => 'delay',
        100 => 'pass',
        200 => 'fail',
        300 => 'ignore'
    ];

    public function getQrCodeAttribute($qrCode)
    {
        return file_url($qrCode);
    }
}

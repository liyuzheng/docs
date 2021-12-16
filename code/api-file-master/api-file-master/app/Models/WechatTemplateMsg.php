<?php


namespace App\Models;


class WechatTemplateMsg extends BaseModel
{
    protected $table    = 'wechat_template_msg';
    protected $fillable = [
        'send_id',
        'receive_id',
        'msg',
        'req_data',
        'resp_data',
        'send_at',
        'error_code',
        'status'
    ];

    const STATUS_DEFAULT = 0;
    const STATUS_SUCCEED = 100;
    const STATUS_FAILED  = 200;
}

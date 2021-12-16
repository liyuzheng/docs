<?php


namespace App\Models;


class PayData extends BaseModel
{
    protected $table    = 'pay_data';
    protected $fillable = ['user_id', 'request_param', 'callback_param', 'done_at', 'request_uri'];
}

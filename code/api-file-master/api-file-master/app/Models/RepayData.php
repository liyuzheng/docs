<?php


namespace App\Models;


class RepayData extends BaseModel
{
    protected $table    = 'repay_data';
    protected $fillable = [
        'repay_id',
        'user_id',
        'request',
        'response',
        'callback',
        'callback_original'
    ];
}

<?php


namespace App\Models;


class DailyRecord extends BaseModel
{
    protected $table    = 'daily_record';
    protected $fillable = [
        'date',
        'active',
        'charm_active',
        'android_charm_active',
        'ios_charm_active',
        'new_user',
        'android_new_user',
        'ios_new_user',
        'trade',
        'android_trade',
        'ios_trade',
        'new_user_trade',
        'android_new_user_trade',
        'ios_new_user_trade',
        'new_member',
        'trade_rate',
        'android_trade_rate',
        'ios_trade_rate',
    ];
}

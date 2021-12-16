<?php


namespace App\Models;


class StatDailyAppName extends BaseModel
{
    protected $table    = 'stat_daily_appname';
    protected $fillable = [
        'date',
        'appname',
        'user_count',
    ];
}

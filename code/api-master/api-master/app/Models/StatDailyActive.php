<?php


namespace App\Models;


class StatDailyActive extends BaseModel
{
    protected $table = 'stat_daily_active';
    protected $fillable = [
        'date',
        'register_count',
        'active_count',
        'member_active_count',
        'charm_register_count',
        'charm_active_count',
        'c_member_active_count',
        'user_register_count',
        'user_active_count',
        't_member_active_count',
    ];

    protected $hidden = ['id', 'date', 'created_at', 'updated_at', 'deleted_at'];
}

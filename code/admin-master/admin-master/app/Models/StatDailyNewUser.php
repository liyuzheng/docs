<?php


namespace App\Models;


class StatDailyNewUser extends BaseModel
{
    protected $table    = 'stat_daily_new_user';
    protected $fillable = [
        'date',
        'os',
        'new_user_count',
        'new_recharge_count',
        'new_member_count',
        'old_member_count'
    ];

    protected $hidden = ['id', 'date', 'created_at', 'updated_at', 'deleted_at'];

    const OS_ALL     = 'all';
    const OS_IOS     = 'ios';
    const OS_ANDROID = 'android';

    const USER_DETAIL_OS_MAPPING = [
        UserDetail::REG_OS_IOS     => self::OS_IOS,
        UserDetail::REG_OS_ANDROID => self::OS_ANDROID,
    ];
}

<?php


namespace App\Models;


class StatDailyInvite extends BaseModel
{
    protected $table = 'stat_daily_invite';

    protected $fillable = [
        'date',
        'os',
        'user_count',
        'recharge_total',
        'charm_count',
        'man_count',
        'current_user_count',
        'current_recharge_total',
        'current_charm_count',
        'current_man_count',
    ];

    protected $hidden = ['id', 'date', 'created_at', 'updated_at', 'deleted_at'];

    const OS_ALL     = 'all';
    const OS_IOS     = 'ios';
    const OS_ANDROID = 'android';

    const USER_DETAIL_OS_MAPPING = [
        UserDetail::REG_OS_IOS     => self::OS_IOS,
        UserDetail::REG_OS_ANDROID => self::OS_ANDROID,
    ];

    public function getRechargeTotalAttribute($total)
    {
        return $total / 100;
    }

    public function getCurrentRechargeTotalAttribute($total)
    {
        return $total / 100;
    }
}

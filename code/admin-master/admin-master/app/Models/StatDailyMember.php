<?php


namespace App\Models;


class StatDailyMember extends BaseModel
{
    protected $table = 'stat_daily_member';
    protected $fillable = [
        'os',
        'member_count',
        'level',
        'not_renewal_count',
        'valid_count',
        'current_expired_count',
        'current_renewal_count',
        'expired_count',
        'renewal_count'
    ];

    protected $appends = ['renewal_percet', 'current_renewal_percet'];
    protected $hidden = ['id', 'date', 'created_at', 'updated_at', 'deleted_at'];

    const OS_IOS     = 'ios';
    const OS_ANDROID = 'android';

    const USER_DETAIL_OS_MAPPING = [
        UserDetail::REG_OS_IOS     => self::OS_IOS,
        UserDetail::REG_OS_ANDROID => self::OS_ANDROID,
    ];

    const STAT_CARD_LEVELS = [
        0                      => 'all',
        Card::LEVEL_WEEK       => 'week',
        Card::LEVEL_HALF_MONTH => 'half_month',
        Card::LEVEL_MONTH      => 'month',
        Card::LEVEL_SEASON     => 'season',
        Card::LEVEL_HALF_YEAR  => 'half_year',
    ];

    public function getLevelAttribute($level)
    {
        return self::STAT_CARD_LEVELS[$level];
    }

    /**
     * 总复购率
     * @return float|int
     */
    public function getRenewalPercetAttribute()
    {
        return $this->expired_count
            ? sprintf('%.0f%%', $this->renewal_count / $this->expired_count * 100)
            : '0%';
    }

    /**
     * 今日复购率
     * @return float|int
     */
    public function getCurrentRenewalPercetAttribute()
    {
        return $this->current_expired_count
            ? sprintf('%.0f%%', $this->current_renewal_count / $this->current_expired_count * 100)
            : '0%';
    }
}

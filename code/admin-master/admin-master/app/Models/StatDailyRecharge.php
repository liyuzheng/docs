<?php


namespace App\Models;


class StatDailyRecharge extends BaseModel
{
    protected $table = 'stat_daily_recharge';
    protected $fillable = [
        'date',
        'os',
        'top_up_total',
        'new_user_total',
        'old_user_total',
        'man_total',
        'woman_total'
    ];

    protected $hidden = ['id', 'date', 'created_at', 'updated_at', 'deleted_at'];

    const OS_ALL     = 'all';
    const OS_IOS     = 'ios';
    const OS_ANDROID = 'android';

    const USER_DETAIL_OS_MAPPING = [
        UserDetail::REG_OS_IOS     => self::OS_IOS,
        UserDetail::REG_OS_ANDROID => self::OS_ANDROID,
    ];

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        foreach ($attributes as $key => $attribute) {
            if (is_numeric($attribute)) {
                $attributes[$key] = $attribute / 100;
            }
        }

        return $attributes;
    }
}

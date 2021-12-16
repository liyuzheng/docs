<?php


namespace App\Models;


class StatDailyTrade extends BaseModel
{
    protected $table    = 'stat_daily_trade';
    protected $fillable = [
        'date',
        'recharge_total',
        'alipay_total',
        'wechat_total',
        'iap_total',
        'invite_withdraw',
        'income_withdraw',
    ];
    protected $hidden = ['id', 'date', 'created_at', 'updated_at', 'deleted_at'];

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

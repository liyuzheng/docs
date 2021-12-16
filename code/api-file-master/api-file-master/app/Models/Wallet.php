<?php


namespace App\Models;


class Wallet extends BaseModel
{
    protected $table      = 'wallet';
    protected $primaryKey = 'user_id';
    protected $fillable   = [
        'user_id',
        'balance',
        'income',
        'income_total',
        'income_invite',
        'income_invite_total',
        'free_vip',
        'free_vip_total'
    ];

    /**
     * @param $seconds
     *
     * @return float|int
     */
    public function getFreeVipAttribute($seconds)
    {
        return $seconds / 86400;
    }

    /**
     * @param $seconds
     *
     * @return float|int
     */
    public function getFreeVipTotalAttribute($seconds)
    {
        return $seconds / 86400;
    }

    /**
     * @param $income
     *
     * @return float
     */
    public function getIncomeInviteAttribute($income)
    {
        return round($income / 100, 2);
    }

    /**
     * @param $income
     *
     * @return float
     */
    public function getIncomeInviteTotalAttribute($income)
    {
        return round($income / 100, 2);
    }
}

<?php


namespace App\Models;


class UnlockPreOrder extends BaseModel
{
    protected $table = 'unlock_pre_order';
    protected $fillable = [
        'user_id',
        'target_user_id',
        'buy_id',
        'user_trigger_at',
        't_user_trigger_at',
        'done_at',
        'status',
        'expired_at'
    ];

    const STATUS_DEFAULT = 0;
    const STATUS_REFUND  = 1;

    /**
     * @param  null|int  $current
     *
     * @return int
     */
    public static function getExpiredAt($current = null)
    {
        $current = $current ?? time();

        return $current + (app()->environment('production') ? 86400 : 600);
    }
}

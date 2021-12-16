<?php


namespace App\Models;


class UserAsset extends BaseModel
{
    protected $primaryKey = 'user_id';
    protected $table      = 'user_asset';
    protected $fillable   = [
        'user_id',
        'balance',
        'rmb_amount',
        'amount',
        'income',
        'income_total',
        'income_invite',
        'income_red_packet',
        'income_invite_total',
        'withdraw_red_packet_total',
        'withdraw_total',
        'cost',
        'cost_grade',
        'income_red_packet',
        'income_red_packet_total',
        'lottery_ticket',
        'income_charm',
    ];
    protected $hidden     = [
        'user_id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}
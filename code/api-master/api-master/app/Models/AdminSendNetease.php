<?php


namespace App\Models;


class AdminSendNetease extends BaseModel
{
    protected $table    = 'admin_send_netease';
    protected $fillable = ['type', 'msg', 'target_id', 'operator'];

    const TYPE_STRONG_REMIND = 100;// 强提醒消息

    public function operator()
    {
        return $this->hasOne(Admin::class, 'id', 'operator');
    }
}

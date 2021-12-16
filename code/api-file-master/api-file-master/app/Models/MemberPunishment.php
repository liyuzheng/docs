<?php


namespace App\Models;


class MemberPunishment extends BaseModel
{
    protected $table    = 'member_punishment';
    protected $fillable = ['user_id', 'type', 'value', 'operator'];
    protected $hidden   = ['id', 'user_id', 'created_at', 'updated_at', 'deleted_at'];

    const TYPE_MEMBER = 100;
    const TYPE_TASK   = 101;

    const MESSAGE_MAPPING = [
        1 => '24小时',
        2 => '72小时',
    ];

    public function operator()
    {
        return $this->hasOne(Admin::class, 'id', 'operator');
    }
}

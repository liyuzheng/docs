<?php


namespace App\Models;


class OperatorSpecialLog extends BaseModel
{
    protected $table    = 'operator_special_log';
    protected $fillable = [
        'target_user_id',
        'action',
        'action_result',
        'content',
        'admin_id'
    ];
    protected $hidden   = ['id', 'admin_id', 'created_at', 'updated_at', 'deleted_at'];

    public function operator()
    {
        return $this->hasOne(Admin::class, 'id', 'admin_id');
    }
}

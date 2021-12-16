<?php


namespace App\Models;


class Report extends BaseModel
{
    protected $table    = 'report';
    protected $hidden   = ['id', 'related_type', 'related_id', 'user_id', 'created_at', 'updated_at', 'deleted_at'];
    protected $fillable = [
        'uuid',
        'related_type',
        'related_id',
        'user_id',
        'reason',
        'status'
    ];

    const RELATED_TYPE_USER   = 100;
    const RELATED_TYPE_APP    = 200;
    const RELATED_TYPE_MOMENT = 300;

    const STATUS_DELAY   = 0;
    const STATUS_FINISH  = 100;
    const STATUS_DISMISS = 200;

    public function reportUser()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function reportedUser()
    {
        return $this->hasOne(User::class, 'id', 'related_id');
    }

    /**
     * uuid转字符串
     *
     * @param $value
     *
     * @return string
     */
    public function getUuidAttribute($value)
    {
        return (string)$value;
    }

    public function getStatusAttribute($status)
    {
        return $status == 0 ? 'delay' : 'finish';
    }
}

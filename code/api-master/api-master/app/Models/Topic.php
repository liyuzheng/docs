<?php


namespace App\Models;


class Topic extends BaseModel
{
    protected $table    = 'topic';
    protected $fillable = ['uuid', 'name', 'status', 'sort', 'desc'];
    protected $hidden   = ['id', 'created_at', 'updated_at', 'deleted_at'];

    const STATUS_OPEN  = 1;
    const STATUS_CLOSE = 0;

    public function getUuidAttribute($uuid)
    {
        return (string)$uuid;
    }
}

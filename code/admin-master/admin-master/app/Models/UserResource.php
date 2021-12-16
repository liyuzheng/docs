<?php


namespace App\Models;


class UserResource extends BaseModel
{
    protected $table    = 'user_resource';
    protected $fillable = ['uuid', 'user_id', 'type', 'resource_id'];

    const  TYPE_AVATAR = 100;
    const  TYPE_PHOTO  = 200;

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
}
<?php


namespace App\Models;


class UserRole extends BaseModel
{
    protected $table    = 'user_role';
    protected $fillable = ['uuid', 'user_id', 'role_id'];

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
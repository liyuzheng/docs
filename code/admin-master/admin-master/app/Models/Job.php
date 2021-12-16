<?php


namespace App\Models;


class Job extends BaseModel
{
    protected $table  = 'job';
    protected $hidden = ['id', 'created_at', 'updated_at', 'deleted_at'];

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

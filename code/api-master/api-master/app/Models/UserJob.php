<?php


namespace App\Models;


class UserJob extends BaseModel
{
    protected $table    = 'user_job';
    protected $fillable = ['uuid', 'user_id', 'job_id'];

    public function job()
    {
        return $this->hasOne(Job::class, 'id', 'job_id');
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
}
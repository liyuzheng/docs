<?php


namespace App\Models;


class UserEvaluate extends BaseModel
{
    protected $table    = 'user_evaluate';
    protected $fillable = [
        'uuid',
        'user_id',
        'target_user_id',
        'tag_id',
        'star'
    ];
    protected $hidden   = ['tag_id', 'created_at', 'updated_at', 'deleted_at'];

    public function tag()
    {
        return $this->hasOne(Tag::class, 'id', 'tag_id');
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
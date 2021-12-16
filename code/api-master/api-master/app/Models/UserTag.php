<?php


namespace App\Models;


class UserTag extends BaseModel
{
    protected $table    = 'user_tag';
    protected $fillable = ['uuid', 'user_id', 'tag_id'];

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
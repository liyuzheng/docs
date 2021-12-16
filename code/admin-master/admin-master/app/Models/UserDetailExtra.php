<?php


namespace App\Models;


class UserDetailExtra extends BaseModel
{
    protected $table    = 'user_detail_extra';
    protected $fillable = [
        'user_id',
        'emotion',
        'child',
        'education',
        'income',
        'figure',
        'smoke',
        'drink'
    ];
    protected $hidden   = ['user_id', 'created_at', 'updated_at', 'deleted_at'];

    public function emotion()
    {
        return $this->hasOne(Tag::class, 'id', 'emotion');
    }

    public function child()
    {
        return $this->hasOne(Tag::class, 'id', 'child');
    }

    public function education()
    {
        return $this->hasOne(Tag::class, 'id', 'education');
    }

    public function income()
    {
        return $this->hasOne(Tag::class, 'id', 'income');
    }

    public function figure()
    {
        return $this->hasOne(Tag::class, 'id', 'figure');
    }

    public function smoke()
    {
        return $this->hasOne(Tag::class, 'id', 'smoke');
    }

    public function drink()
    {
        return $this->hasOne(Tag::class, 'id', 'drink');
    }
}

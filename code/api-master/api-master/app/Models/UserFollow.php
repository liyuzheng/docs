<?php


namespace App\Models;


class UserFollow extends BaseModel
{
    protected $table    = 'user_follow';
    protected $fillable = ['user_id', 'follow_id'];
}
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
}

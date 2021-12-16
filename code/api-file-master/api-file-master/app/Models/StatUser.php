<?php


namespace App\Models;


class StatUser extends BaseModel
{
    protected $table    = 'stat_user';
    protected $fillable = [
        'user_id',
        'first_top_up_seconds'
    ];
}

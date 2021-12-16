<?php


namespace App\Models;


class UserDestroy extends BaseModel
{
    protected $table    = 'user_destroy';
    protected $fillable = [
        'user_id',
        'destroy_at',
        'cancel_at'
    ];
}

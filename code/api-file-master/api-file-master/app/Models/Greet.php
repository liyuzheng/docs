<?php


namespace App\Models;


class Greet extends BaseModel
{
    protected $table    = 'greet';
    protected $fillable = [
        'user_id',
        'target_id',
    ];
}

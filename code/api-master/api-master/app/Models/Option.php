<?php


namespace App\Models;


class Option extends BaseModel
{
    protected $table    = 'option';
    protected $fillable = ['p_id', 'name', 'url', 'type', 'code'];

    const TYPE_FRONT = 100;
    const TYPE_BACK  = 200;
}

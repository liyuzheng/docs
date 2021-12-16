<?php


namespace App\Models;


class Authority extends BaseModel
{
    protected $table    = 'authority';
    protected $fillable = ['role_id', 'option_id'];
}

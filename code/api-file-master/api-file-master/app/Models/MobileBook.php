<?php


namespace App\Models;


class MobileBook extends BaseModel
{
    protected $table    = 'mobile_book';
    protected $fillable = ['user_id', 'name', 'mobile'];
}
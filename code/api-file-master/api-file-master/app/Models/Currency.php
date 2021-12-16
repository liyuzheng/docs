<?php


namespace App\Models;


class Currency extends BaseModel
{
    protected $table = 'currency';
    protected $hidden = ['id'];

    public function getAmountAttribute($amount)
    {
        return $amount / 10;
    }
}

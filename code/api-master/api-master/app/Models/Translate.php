<?php


namespace App\Models;


class Translate extends BaseModel
{
    protected $table    = 'translate';
    protected $fillable = ['os', 'key', 'chinese', 'tw_traditional', 'xg_traditional', 'english'];

    const OS_ANDROID = 100;
    const OS_IOS     = 200;
    const OS_COMMON  = 300;
    const OS_SERVER  = 400;
}

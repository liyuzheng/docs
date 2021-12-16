<?php


namespace App\Models;


class FengkongCheckPic extends BaseModel
{
    protected $table = 'fengkong_check_pic';
    protected $fillable = [
        'bt_id',
        'url',
        'risk_level',
        'request_id'
    ];
}

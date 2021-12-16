<?php


namespace App\Models;


class FaceRecord extends BaseModel
{
    protected $table    = 'face_record';
    protected $fillable = [
        'user_id',
        'request_id',
        'token',
        'biz_id'
    ];
}

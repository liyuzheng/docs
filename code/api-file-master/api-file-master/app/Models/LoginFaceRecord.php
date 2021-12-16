<?php


namespace App\Models;


class LoginFaceRecord extends BaseModel
{
    protected $table    = 'login_face_record';
    protected $fillable = [
        'user_id',
        'biz_id',
        'request_id',
        'token',
        'login_status',
        'face_pic'
    ];

    const LOGIN_SUCCESS = 100;
    const LOGIN_FAIL    = 200;
}

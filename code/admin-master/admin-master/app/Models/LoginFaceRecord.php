<?php


namespace App\Models;


class LoginFaceRecord extends BaseModel
{
    protected $table = 'login_face_record';

    const LOGIN_STATUS_SUCCESS = 100;
    const LOGIN_STATUS_FAIL    = 200;

    const LOGIN_STATUS_MAPPING = [
        0   => '未登录',
        100 => '登陆成功',
        200 => '登录失败',
    ];
}

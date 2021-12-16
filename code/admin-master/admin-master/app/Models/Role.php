<?php


namespace App\Models;


class Role extends BaseModel
{
    protected $table    = 'role';
    protected $fillable = [
        'uuid',
        'name',
        'key',
        'icon',
        'is_default',
        'desc'
    ];
    const KEY_USER        = 'user';
    const KEY_AUTH_USER   = 'auth_user';
    const KEY_AUTH_MEMBER = 'auth_member';
    const KEY_CHARM_GIRL  = 'charm_girl';

    //通用角色
    const TYPE_COMMON = 100;

    /**
     * uuid转字符串
     *
     * @param $value
     *
     * @return string
     */
    public function getUuidAttribute($value)
    {
        return (string)$value;
    }
}

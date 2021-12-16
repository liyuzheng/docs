<?php


namespace App\Models;


class UserDetail extends BaseModel
{
    protected $table      = 'user_detail';
    protected $primaryKey = 'user_id';
    protected $fillable   = [
        'uuid',
        'user_id',
        'intro',
        'channel',
        'reg_version',
        'run_version',
        'os',
        'os_sys',
        'reg_os',
        'height',
        'weight',
        'region',
        'client_id',
        'client_name',
        'reg_schedule',
        'invite_code',
        'inviter',
        'follow_count',
        'followed_count',
        'invite_count'
    ];
    protected $hidden     = ['user_id'];

    const REG_SCHEDULE_FINISH   = 100;
    const REG_SCHEDULE_GENDER   = 101;
    const REG_SCHEDULE_BASIC    = 102;
    const REG_SCHEDULE_RELATION = 103;
    const REG_SCHEDULE_PASSWORD = 104;

    const REG_OS_IOS     = 'ios';
    const REG_OS_ANDROID = 'android';

    const FAKE_INTRO = '他很神秘，什么都没都没有写。';

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

    public function getRegionAttribute($region)
    {
        return $region === '' ? '神秘星球' : $region;
    }
    //
    //    public function getIntroAttribute($intro)
    //    {
    //        return $intro === '' ? self::FAKE_INTRO : $intro;
    //    }
}

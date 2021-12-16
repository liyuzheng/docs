<?php


namespace App\Models;


class Version extends BaseModel
{
    protected $table    = 'version';
    protected $fillable = ['appname', 'os', 'bundle_id', 'version', 'channel', 'notice', 'url', 'audited_at'];

    const OS_IOS         = 100; // ios
    const OS_ANDROID     = 200; // 安卓
    const OS_STR_IOS     = 'ios';
    const OS_STR_ANDROID = 'android';

    const OS_MAPPING = [
        self::OS_IOS         => self::OS_STR_IOS,
        self::OS_ANDROID     => self::OS_STR_ANDROID,
        self::OS_STR_IOS     => self::OS_IOS,
        self::OS_STR_ANDROID => self::OS_ANDROID,
    ];
}

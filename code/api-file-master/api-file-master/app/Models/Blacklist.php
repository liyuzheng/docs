<?php


namespace App\Models;


class Blacklist extends BaseModel
{
    protected $table    = 'blacklist';
    protected $hidden   = ['related_type', 'related_id', 'user_id', 'created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['related_type', 'related_id', 'user_id', 'reason', 'remark', 'expired_at'];

    const RELATED_TYPE_MOBILE  = 100;//通讯录屏蔽用户
    const RELATED_TYPE_MANUAL  = 200;//手动拉黑
    const RELATED_TYPE_OVERALL = 300;//全局拉黑
    const RELATED_TYPE_CLIENT  = 400;//设备拉黑
    const RELATED_TYPE_FACE    = 500;//人脸拉黑

    const RELATED_TYPE_ARR = [
        self::RELATED_TYPE_MOBILE  => '通讯录屏蔽用户',
        self::RELATED_TYPE_MANUAL  => '手动拉黑',
        self::RELATED_TYPE_OVERALL => '全局拉黑',
        self::RELATED_TYPE_CLIENT  => '设备拉黑',
        self::RELATED_TYPE_FACE    => '人脸拉黑',
    ];

    public function relatedUser()
    {
        return $this->hasOne(User::class, 'id', 'related_id');
    }
}

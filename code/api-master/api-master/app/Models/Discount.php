<?php


namespace App\Models;

use Carbon\Carbon;

class Discount extends BaseModel
{
    protected $table = 'discount';
    protected $fillable = [
        'related_type',
        'related_id',
        'user_id',
        'pay_id',
        'done_at',
        'platform',
        'discount',
        'type',
        'status',
        'expired_at'
    ];


    public const  RELATED_TYPE_TENCENT      = 98;  // 公众号充值折扣 (表中无数据)
    public const  RELATED_TYPE_RENEWAL      = 99;  // 限时续费折扣 (表中无数据)
    public const  RELATED_TYPE_MANUAL       = 100; // 手动设置
    public const  RELATED_TYPE_INVITE       = 101; // 完成邀请人填写赠送
    public const  RELATED_TYPE_INVITE_PRIZE = 102; // 邀请到新用户奖励
    public const  RELATED_TYPE_GIVING       = 200; // 第N天活跃赠送

    /** @var int web:公众号  android: 安卓  native_web:ios网页充值 */
    public const  PLATFORM_COMMON         = 0;
    public const  PLATFORM_WEB            = 100;
    public const  PLATFORM_ANDROID        = 101;
    public const  PLATFORM_NATIVE_IOS_WEB = 102;
    public const  PLATFORM_IOS            = 103;
    public const  PLATFORM_COMMON_STR     = 'common';
    public const  PLATFORM_WEB_STR        = 'web';
    public const  PLATFORM_ANDROID_STR    = 'android';
    public const  PLATFORM_NATIVE_WEB_STR = 'native_web';
    public const  PLATFORM_IOS_STR        = 'ios';

    const PLATFORM_MAPPING = [
        self::PLATFORM_WEB_STR        => self::PLATFORM_WEB,
        self::PLATFORM_ANDROID_STR    => self::PLATFORM_ANDROID,
        self::PLATFORM_NATIVE_WEB_STR => self::PLATFORM_NATIVE_IOS_WEB,
        self::PLATFORM_COMMON_STR     => self::PLATFORM_COMMON,
        self::PLATFORM_IOS_STR        => self::PLATFORM_IOS,
    ];

    const TYPE_NOT_OVERLAP = 100;          // 不能重叠使用的优惠卷 (多张100不可一起使用, 一张100和多张200可重叠使用)
    const TYPE_CAN_OVERLAP = 200;          // 可重叠使用的优惠卷

    const STATUS_DEFAULT   = 0; // 默认状态
    const STATUS_ABANDONED = 1; // 优惠卷废弃状态

    const NOT_CONTINUOUS_CARD_DISCOUNT  = 0.8;           // 安卓三天内到期续费折扣
    const TENCENT_CARD_DISCOUNT         = 0.95;          // 公众号充值折扣
    const APPLET_INVITE_TARGET_DISCOUNT = 0.8;           // 小程序邀请被邀请用户会员折扣
    const ACTIVE_GIVING_DISCOUNT        = 0.7;           // 活跃第N天赠送的折扣

    const MIN_DISCOUNT = 0.5; // 允许出现最低的折扣

    protected $appends = ['done_format'];

    public function getDoneFormatAttribute()
    {
        return $this->done_at !== 0 ? Carbon::createFromTimeStamp($this->done_at)->format('Y-m-d H:i:s') : 0;
    }
}

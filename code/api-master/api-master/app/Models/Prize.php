<?php


namespace App\Models;


class Prize extends BaseModel
{
    protected $table    = 'prize';
    protected $fillable = [
        'related_type',
        'type',
        'desc',
        'value'
    ];

    const TYPE_MAN_INVITE              = 100; // 邀请用户1天会员奖励
    const TYPE_MAN_INVITE_MEMBER       = 101; // 邀请用户充值会员5天会员奖励
    const TYPE_WOMAN_INVITE_MEMBER     = 102; // app内被女用户邀请的用户充值会员现金奖励
    const TYPE_APPLET_WEEK_MEMBER      = 103; // 小程序渠道邀请用户充值周会员现金奖励
    const TYPE_APPLET_MONTH_MEMBER     = 104; // 小程序渠道邀请用户充值月会员现金奖励
    const TYPE_APPLET_SEASON_MEMBER    = 105; // 小程序渠道邀请用户充值季会员现金奖励
    const TYPE_APPLET_HALF_YEAR_MEMBER = 106; // 小程序渠道邀请用户充值半年会员现金奖励
    const TYPE_PUNISHMENT_MEMBER       = 107; // 异常邀请惩罚奖励
    const TYPE_CUMULATIVE_DISCOUNT     = 200; // 男生新邀请可累计折扣

    const RELATED_TYPE_MEMBER   = 100; // 会员奖励
    const RELATED_TYPE_CASH     = 200; // 现金奖励
    const RELATED_TYPE_DISCOUNT = 300; // 会员折扣奖励

    const PRIZE_MEMBER_TYPE_MAPPING = [
        self::TYPE_MAN_INVITE        => MemberRecord::TYPE_INVITE_USER,
        self::TYPE_PUNISHMENT_MEMBER => MemberRecord::TYPE_INVITE_USER,
        self::TYPE_MAN_INVITE_MEMBER => MemberRecord::TYPE_INVITE_USER_MEMBER,
    ];

    // 会员卡级别与奖励类型映射表
    const MEMBER_LEVEL_TYPE_MAPPING = [
        Card::LEVEL_WEEK       => self::TYPE_APPLET_WEEK_MEMBER,
        Card::LEVEL_HALF_MONTH => self::TYPE_APPLET_WEEK_MEMBER,
        Card::LEVEL_MONTH      => self::TYPE_APPLET_MONTH_MEMBER,
        Card::LEVEL_SEASON     => self::TYPE_APPLET_SEASON_MEMBER,
        Card::LEVEL_HALF_YEAR  => self::TYPE_APPLET_HALF_YEAR_MEMBER,
    ];
}

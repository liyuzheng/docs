<?php


namespace App\Models;


class MemberRecord extends BaseModel
{
    protected $table = 'member_record';
    protected $fillable = [
        'type',
        'user_id',
        'pay_id',
        'duration',
        'first_start_at',
        'next_cycle_at',
        'expired_at',
        'certificate',
        'status'
    ];

    const STATUS_DEFAULT       = 0; // 默认状态
    const STATUS_BE_INHERITED  = 1; // 被继承
    const STATUS_INHERITED     = 2; // 继承
    const STATUS_REFUND        = 3; // 退款
    const STATUS_GIVE_REGISTER = 4; // 恢复赠送的会员

    const STATUS_VALID   = [self::STATUS_DEFAULT, self::STATUS_INHERITED];
    const STATUS_INVALID = [self::STATUS_BE_INHERITED, self::STATUS_REFUND];

    const TYPE_BUY                = 100; //购买
    const TYPE_INVITE_USER        = 200; //邀请用户
    const TYPE_INVITE_USER_MEMBER = 201; //邀请用户成为会员
    const TYPE_CURRENCY_BUY       = 300; //代币购买
}

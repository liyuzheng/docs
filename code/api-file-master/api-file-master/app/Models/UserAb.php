<?php


namespace App\Models;


class UserAb extends BaseModel
{
    protected $table = 'user_ab';
    protected $fillable = ['type', 'user_id'];

    const TYPE_HALF_MONTH_TEST_A     = 101;
    const TYPE_HALF_MONTH_TEST_B     = 102;
    const TYPE_MAN_INVITE_TEST_A     = 201;
    const TYPE_MAN_INVITE_TEST_B     = 202;
    const TYPE_GOLD_TRADE_TEST_A     = 301;
    const TYPE_GOLD_TRADE_TEST_B     = 302;
    const TYPE_NEW_GOLD_TRADE_TEST_A = 401;
    const TYPE_NEW_GOLD_TRADE_TEST_B = 402;
    const TYPE_MEMBER_PRICE_TEST_A   = 501;
    const TYPE_MEMBER_PRICE_TEST_B   = 502;
    const TYPE_MEMBER_PRICE_TEST_C   = 503;
    const TYPE_MEMBER_PRICE_TEST_D   = 504;
    const TYPE_MEMBER_PRICE_TEST_E   = 505;

    /**
     *
     * @return bool
     */
    public function inviteTestIsB()
    {
        return $this->getRawOriginal('type') == self::TYPE_MAN_INVITE_TEST_B;
    }
}

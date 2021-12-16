<?php


namespace App\Models;


class UserAb extends BaseModel
{
    protected $table = 'user_ab';
    protected $fillable = ['type', 'user_id'];

    const TYPE_HALF_MONTH_TEST_A = 101;
    const TYPE_HALF_MONTH_TEST_B = 102;
    const TYPE_MAN_INVITE_TEST_A = 201;
    const TYPE_MAN_INVITE_TEST_B = 202;
    const TYPE_GOLD_TRADE_TEST_A = 301;
    const TYPE_GOLD_TRADE_TEST_B = 302;

    /**
     *
     * @return bool
     */
    public function inviteTestIsB()
    {
        return $this->getRawOriginal('type') == self::TYPE_MAN_INVITE_TEST_B;
    }
}

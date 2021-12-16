<?php


namespace App\Models;


class Card extends BaseModel
{
    protected $table = 'card';
    protected $hidden = ['id',];

    const TYPE_MEMBER       = 100; //会员卡
    const TYPE_PRIZE_MEMBER = 101; //赠送的会员卡

    const LEVEL_MONTH      = 100;     //月卡
    const LEVEL_SEASON     = 200;     //季卡
    const LEVEL_YEAR       = 300;     //年卡
    const LEVEL_WEEK       = 400;     //周卡
    const LEVEL_HALF_YEAR  = 500;     //半年卡
    const LEVEL_HALF_MONTH = 700;     //半月卡
    const LEVEL_FREE_VIP   = 600;     //增送卡

    const CARD_LEVEL_SCORE = [
        self::LEVEL_FREE_VIP   => 0,
        self::LEVEL_WEEK       => 1,
        self::LEVEL_HALF_MONTH => 2,
        self::LEVEL_MONTH      => 3,
        self::LEVEL_SEASON     => 4,
        self::LEVEL_HALF_YEAR  => 5,
        self::LEVEL_YEAR       => 6
    ];

    public function getDuration($timestamp = 0)
    {
        $currentNow = time();
        $timestamp  = $timestamp < $currentNow ? $currentNow : $timestamp;

        switch ($this->level) {
            case self::LEVEL_WEEK:
                return strtotime('+7 days', $timestamp) - $timestamp;
            case self::LEVEL_HALF_YEAR:
                return strtotime('+6 months', $timestamp) - $timestamp;
            case self::LEVEL_YEAR:
                return strtotime('+1 years', $timestamp) - $timestamp;
            case self::LEVEL_SEASON:
                return strtotime('+3 months', $timestamp) - $timestamp;
            case self::LEVEL_HALF_MONTH:
                return strtotime('+15 days', $timestamp) - $timestamp;
                break;
            case self::LEVEL_MONTH:
            default:
                return strtotime('+1 months', $timestamp) - $timestamp;
        }
    }

    public function getExtraAttribute($extra)
    {
        return is_array($extra) ? $extra : json_decode($extra, true);
    }

    public static function getAveragePriceByLevelAndPrice(int $price, int $level)
    {
        switch ($level) {
            case self::LEVEL_WEEK:
                $days = 7;
                break;
            case self::LEVEL_HALF_YEAR:
                $days = 186;
                break;
            case self::LEVEL_YEAR:
                $days = 365;
                break;
            case self::LEVEL_SEASON:
                $days = 93;
                break;
            case self::LEVEL_HALF_MONTH:
                $days = 15;
                break;
            case self::LEVEL_MONTH:
            default:
                $days = 31;
        }

        return (float)substr(sprintf("%.3f", $price / $days), 0, -1);
    }
}

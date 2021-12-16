<?php


namespace App\Models;


class TradeBuy extends TradeModel
{
    protected $table = 'trade_buy';
    protected $fillable = [
        'user_id',
        'target_user_id',
        'related_type',
        'income_rate',
        'ori_amount',
        'amount',
        'before_balance',
        'after_balance',
        'before_income',
        'after_income'
    ];

    protected $hidden = ['id', 'user_id', 'target_user_id', 'related_type', 'created_at', 'updated_at', 'deleted_at'];
    protected $appends = ['event_at'];

    const TRADE_BUY_SHARE_RATIO = 0;

    const RELATED_TYPE_BUY_PRIVATE_CHAT = 100; // 购买私信
    const RELATED_TYPE_BUY_WECHAT       = 200; // 购买微信
    const RELATED_TYPE_BUY_PHOTO        = 300; // 购买红包相册

    // 与流水主表 trade_balance related_type 映射表
    const TRADE_RELATED_TYPES = [
        self::RELATED_TYPE_BUY_PRIVATE_CHAT => TradeBalance::RELATED_TYPE_BUY_PRIVATE_CHAT,
        self::RELATED_TYPE_BUY_WECHAT       => TradeBalance::RELATED_TYPE_BUY_WECHAT,
        self::RELATED_TYPE_BUY_PHOTO        => TradeBalance::RELATED_TYPE_BUY_PHOTO,
    ];

    // 相关类型中文提示映射表
    const RELATED_TYPE_TIPS_MAPPING = [
        self::RELATED_TYPE_BUY_PRIVATE_CHAT => '私信',
        self::RELATED_TYPE_BUY_WECHAT       => '微信',
        self::RELATED_TYPE_BUY_PHOTO        => '红包视频',
    ];

    private $recordType = self::INCOME;

    /**
     * @param  int  $recordType
     */
    public function setRecordType(int $recordType): void
    {
        $this->recordType = $recordType;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        switch ($this->recordType) {
            case self::CONSUME:
                return 0 - $this->getRawOriginal('ori_amount');
            case self::INCOME:
            default:
                return $this->getRawOriginal('amount');
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function targetUser()
    {
        return $this->hasOne(User::class, 'id', 'target_user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * @return false|string
     */
    public function getEventAtAttribute()
    {
        return substr(date('Y/m/d H:i', $this->created_at->timestamp), 2);
    }

    /**
     * @param $amount
     *
     * @return float|int
     */
    public function getOriAmountAttribute($amount)
    {
        return $amount / 10;
    }

    /**
     * @param $amount
     *
     * @return float|int
     */
    public function getAmountAttribute($amount)
    {
        return $amount / 10;
    }
}

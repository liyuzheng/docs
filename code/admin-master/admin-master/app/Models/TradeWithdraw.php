<?php


namespace App\Models;


class TradeWithdraw extends TradeModel
{
    protected $table = 'trade_withdraw';
    protected $fillable = [
        'user_id',
        'contact_id',
        'income_rate',
        'ori_amount',
        'amount',
        'type',
        'id_card',
        'before_income',
        'after_income',
        'operator_id',
        'repay_status',
        'done_at'
    ];


    protected $hidden = [
        'user_id',
        'contact_id',
        'income_rate',
        'type',
        'before_income',
        'after_income',
        'done_at',
        'operator_id',
        'updated_at',
        'deleted_at',
        'created_at',
    ];

    protected $appends = ['event_at'];

    const WITHDRAW_RATE   = 0.07;        // 税率
    const TYPE_INCOME     = 100;         // 提现类型:收入提现
    const TYPE_INVITE     = 200;         // 提现类型:邀请提现
    const TYPE_INVITE_STR = 'invite';    // 提现类型:邀请提现
    const TYPE_INCOME_STR = 'income';    // 提现类型:邀请提现

    const TYPE_MAPPING = [
        self::TYPE_INCOME => '收入提现',
        self::TYPE_INVITE => '邀请提现'
    ];

    const TYPE_STR_MAPPING = [
        self::TYPE_INCOME     => self::TYPE_INCOME_STR,
        self::TYPE_INVITE     => self::TYPE_INVITE_STR,
        self::TYPE_INCOME_STR => self::TYPE_INCOME,
        self::TYPE_INVITE_STR => self::TYPE_INVITE,
    ];

    const RELATED_TYPE_TIPS_MAPPING = [
        self::TYPE_INCOME => '收益提现',
        self::TYPE_INVITE => '邀请提现',
    ];

    private $recordType = self::INCOME;

    public const STATUS_NOT_KNOW = 0;//未打款
    public const STATUS_DEFAULT  = 100;//处理中
    public const STATUS_SUCCESS  = 200;//成功
    public const STATUS_FAIL     = 300;//失败

    public function getRelatedType(): int
    {
        return Trade::RELATED_TYPE_WITHDRAW;
    }


    /**
     * @param  int  $recordType
     */
    public function setRecordType(int $recordType): void
    {
        $this->recordType = $recordType;
    }

    public function getAmount(): int
    {
        switch ($this->recordType) {
            case self::CONSUME:
                return 0 - $this->getRawOriginal('ori_amount');
            case self::INCOME:
            default:
                return $this->getRawOriginal('ori_amount');
        }
    }

    /**
     * 根据提现类型获取 wallet 表需要操作对字段
     *
     * @param  int  $type
     *
     * @return string[]
     */
    public static function getAmountFieldsByType(int $type)
    {
        switch ($type) {
            case self::TYPE_INVITE:
                return ['income_invite', 'income_invite_total'];
                break;
            case self::TYPE_INCOME:
            default:
                return ['income', 'income_total'];
        }
    }

    /**
     * @param $amount
     *
     * @return float|int
     */
    public function getOriAmountAttribute($amount)
    {
        return $amount / 100;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function contact()
    {
        return $this->hasOne(UserContact::class, 'id', 'contact_id');
    }

    /**
     * @return false|string
     */
    public function getEventAtAttribute()
    {
        return date('Y/m/d H:i', $this->created_at->timestamp);
    }
}

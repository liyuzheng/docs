<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Relations\HasOne;

class Repay extends BaseModel
{
    protected $table    = 'repay';
    protected $fillable = [
        'user_id',
        'related_type',
        'related_id',
        'request',
        'response',
        'operator_id',
        'amount'
    ];
    public const RELATED_TYPE_WGC = 100;

    public const STATUS_NOT_KNOW = -1;//未打款
    public const STATUS_DEFAULT  = 0;//处理中
    public const STATUS_SUCCESS  = 100;//成功
    public const STATUS_FAIL     = 101;//失败

    /**
     * 请求的记录
     * @return HasOne
     */
    public function repayData() : HasOne
    {
        return $this->hasOne(RepayData::class, 'repay_id', 'id');
    }

}

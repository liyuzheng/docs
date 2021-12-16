<?php


namespace App\Models;


/**
 * 交易相关接口
 *
 * Interface Transaction
 * @package App\Models
 */
abstract class TradeModel extends BaseModel
{
    const  TRADE_RELATED_TYPES = [];
    const  CONSUME             = 0;
    const  INCOME              = 1;

    /**
     * 获取交易金额(分)
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->getRawOriginal('amount');
    }

    /**
     * 获取交易类型
     *
     * @return int
     */
    public function getRelatedType(): int
    {
        return static::TRADE_RELATED_TYPES[$this->getRawOriginal('related_type')];
    }


    /**
     * 获取交易成交时间
     *
     * @return int
     */
    public function getDoneAt(): int
    {
        if (is_null($this->done_at)) {
            if (in_array('done_at', $this->fillable)) {
                return 0;
            }

            return time();
        }

        return $this->getRawOriginal('done_at');
    }
}

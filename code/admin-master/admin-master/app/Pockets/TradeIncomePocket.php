<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\TradeModel;
use App\Models\User;

class TradeIncomePocket extends BasePocket
{
    use TradeRecordTrait;

    /**
     * 创建用户法币相关交易流水记录
     *
     * @param User       $user
     * @param TradeModel $related
     *
     * @return \App\Models\Trade
     */
    public function createRecord(User $user, TradeModel $related)
    {
        return $this->createTradeRecordByUserAndTradeModel($user, $related, rep()->tradeIncome);
    }
}

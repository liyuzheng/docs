<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\TradeModel;
use App\Models\User;

class TradePocket extends BasePocket
{
    use TradeRecordTrait;

    /**
     * 创建用户交易总流水
     *
     * @param User       $user
     * @param TradeModel $related
     *
     * @return \App\Models\Trade
     */
    public function createRecord(User $user, TradeModel $related)
    {
        return $this->createTradeRecordByUserAndTradeModel($user, $related, rep()->trade);
    }

    /**
     * 批量通过子消费流水创建总消费流水
     *
     * @param TradeModel ...$models
     */
    public function batchCreateTradeRecord(TradeModel ...$models)
    {
        $tradeRecords = [];

        foreach ($models as $model) {
            $tradeRecords[] = [
                'user_id'      => $model->user_id,
                'related_type' => $model->getRelatedType(),
                'related_id'   => $model->id,
                'amount'       => $model->getAmount(),
                'done_at'      => $model->getDoneAt(),
                'created_at' => time(),
                'updated_at' => time(),
            ];
        }

        rep()->trade->getQuery()->insert($tradeRecords);
    }

}

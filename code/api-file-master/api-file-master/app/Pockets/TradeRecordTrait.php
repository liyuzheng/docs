<?php


namespace App\Pockets;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\TradeModel;
use App\Models\User;

trait TradeRecordTrait
{
    /**
     * 创建用户交易流水
     *
     * @param User           $user
     * @param TradeModel     $related
     * @param BaseRepository $repository
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function createTradeRecordByUserAndTradeModel(User $user, TradeModel $related, BaseRepository $repository)
    {
        $tradeData = [
            'user_id'      => $user->id,
            'related_type' => $related->getRelatedType(),
            'related_id'   => $related->id,
            'amount'       => $related->getAmount(),
            'done_at'      => $related->getDoneAt()
        ];

        return $repository->getQuery()->create($tradeData);
    }

    abstract public function createRecord(User $user, TradeModel $related);
}

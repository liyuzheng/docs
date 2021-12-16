<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Card;

class CardRepository extends BaseRepository
{
    public function setModel()
    {
        return Card::class;
    }

    /**
     * 通过商品ID获取会员卡详情信息
     *
     * @param  int  $goodId
     *
     * @return \App\Models\Card
     */
    public function getCardByGoodsId(int $goodId)
    {
        return rep()->card->getQuery()->select('card.*')->join('goods', 'goods.related_id', 'card.id')
            ->where('goods.id', $goodId)->first();
    }

    /**
     * 获得赠送的会员卡
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getFreeMemberCard()
    {
        return $this->m()->where('type', Card::TYPE_PRIZE_MEMBER)->first();
    }
}

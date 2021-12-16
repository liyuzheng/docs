<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Card;
use App\Models\Good;
use App\Models\User;
use App\Models\UserAb;
use Illuminate\Support\Collection;

class GoodRepository extends BaseRepository
{
    public function setModel()
    {
        return Good::class;
    }

    /**
     * 根据客户端类型和商品类型获取所有商品
     *
     * @param  \App\Models\User|null  $user
     * @param  int                    $os
     * @param  int                    $relatedType
     * @param  int                    $platform
     * @param  int                    $priceTestType
     * @param  string                 $appName
     * @param  array                  $exceptRelatedIds
     * @param  array                  $exceptIds
     *
     * @return array
     */
    public function getGoodsByRelatedTypeAndOs(
        $user, int $os, string $appName, int $platform,
        int $relatedType, int $priceTestType, $exceptRelatedIds = [], $exceptIds = []
    ) {
        $goods = rep()->good->getQuery()->select('product_id', 'type', 'related_type', 'ori_price', 'price',
            'is_default', 'uuid', 'related_id')->where('os', $os)->where('app_name', $appName)
            ->where('platform', $platform)->where('related_type', $relatedType)
            ->when(!empty($exceptRelatedIds), function ($query) use ($exceptRelatedIds) {
                $query->whereNotIn('related_id', $exceptRelatedIds);
            })->when(!empty($exceptIds), function ($query) use ($exceptIds) {
                $query->whereNotIn('id', $exceptIds);
            })->where('test_type', $priceTestType)->orderBy('price', 'asc')->get();

        return $this->buildInfoAndPayMethodToGoods($goods, $relatedType);
    }

    /**
     * 更具 related_type 获取代币支付商品列表
     *
     * @param  int  $relatedType
     *
     * @return array
     */
    public function getProxyCurrencyGoods(int $relatedType)
    {
        $goods = rep()->good->getQuery()->select('product_id', 'type', 'related_type', 'ori_price', 'price',
            'is_default', 'uuid', 'related_id')->where('related_type', $relatedType)
            ->where('type', Good::TYPE_CURRENCY)->orderBy('sort', 'desc')->get();

        return $this->buildInfoAndPayMethodToGoods($goods, $relatedType);
    }

    /**
     * 给商品列表绑定商品详情信息和支付方式
     *
     * @param  Collection  $collection
     * @param  int         $relatedType
     *
     * @return array
     */
    public function buildInfoAndPayMethodToGoods(Collection $collection, int $relatedType)
    {
        switch ($relatedType) {
            case Good::RELATED_TYPE_CARD:
                $query = rep()->card->getQuery()->select('id', 'uuid', 'name', 'level', 'continuous', 'extra');
                break;
            case Good::RELATED_TYPE_CURRENCY:
            default:
                $query = rep()->currency->getQuery()->select('id', 'amount');
                break;
        }

        $infos             = $query->whereIn('id', $collection->pluck('related_id')->toArray())->get();
        $cacheGoods        = [];
        $groupByPriceGoods = $collection->groupBy('price');

        foreach ($groupByPriceGoods as $price => $priceGoods) {
            foreach ($priceGoods as $priceGood) {
                $index  = $price . '_' . $priceGood->related_id . $priceGood->getRawOriginal('related_type');
                $detail = ['uuid' => $priceGood->uuid, 'type' => $priceGood->type];

                if (!isset($cacheGoods[$index])) {
                    $priceGood->setAttribute('details', [$detail]);
                    $priceGood->setRelation('info', $infos->find($priceGood->related_id));
                    $cacheGoods[$index] = $priceGood;
                } else {
                    $cacheGoods[$index]->setAttribute('details',
                        array_merge($cacheGoods[$index]->details, [$detail]));
                }
            }
        }

        return $cacheGoods;
    }

    /**
     * 绑定商品详情信息
     *
     * @param  Good  $good
     *
     * @return Good
     */
    public function buildInfoAndPayByGood(Good $good)
    {
        switch ($good->getRawOriginal('related_type')) {
            case Good::RELATED_TYPE_CARD:
                $query = rep()->card->getQuery()->select('id', 'uuid', 'name', 'level',
                    'continuous', 'extra');
                break;
            case Good::RELATED_TYPE_CURRENCY:
            default:
                $query = rep()->currency->getQuery()->select('id', 'amount');
                break;
        }

        $info = $query->where('id', $good->related_id)->first();
        $good->setAttribute('details', [['uuid' => $good->uuid, 'type' => $good->type]]);
        $good->setAttribute('info', $info);

        return $good;
    }

    /**
     * 通过 level 获取单独的会员卡商品
     *
     * @param  string  $appName
     * @param  int     $os
     * @param  int     $platform
     * @param          $level
     *
     * @return array
     */
    public function getSingleCardGoodsByLevel(string $appName, int $os, int $platform, $level)
    {
        if ($level == Card::LEVEL_YEAR || $level = Card::LEVEL_FREE_VIP) {
            $level = Card::LEVEL_MONTH;
        }
        //@optimize 去掉join 查询两次都可以
        $query = $this->getQuery()->join('card', 'card.id', 'goods.related_id')
            ->where('goods.platform', $platform)
            ->where('goods.os', $os)->where('related_type', Good::RELATED_TYPE_CARD)
            ->where('card.level', $level);
        if ($os == Good::CLIENT_OS_IOS) {
            $query->where('goods.app_name', $appName);
        }

        $cardGood  = null;
        $uuidQuery = clone $query;
        $goods     = $query->select('product_id', 'goods.type', 'related_type', 'price',
            'is_default', 'goods.uuid', 'related_id')->get();
        if ($goods->count()) {
            $card = $uuidQuery->select('card.id', 'card.uuid')->first();

            $details = [];
            foreach ($goods as $good) {
                $details[] = ['uuid' => $good->uuid, 'type' => $good->type];
            }

            /** @var \App\Models\Good $cardGood */
            $cardGood = $goods->first()->setAttribute('details', $details);
        } else {
            $card = rep()->card->getQuery()->select('card.id', 'card.uuid')
                ->where('level', $level)->where('continuous', 0)->first();
        }

        return [$cardGood, $card];
    }
}

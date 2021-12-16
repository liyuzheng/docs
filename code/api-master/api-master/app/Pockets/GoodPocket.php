<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\Card;
use App\Models\Discount;
use App\Models\Good;
use App\Models\SwitchModel;
use App\Models\TradePay;
use App\Models\User;
use App\Models\UserAb;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis;

class GoodPocket extends BasePocket
{
    /**
     * 根据客户端类型和商品类型获取商品列表
     *
     * @param  \App\Models\User|null  $user
     * @param  int                    $os
     * @param  int                    $relatedType
     * @param  int                    $platform
     * @param  string                 $appName
     *
     * @return array
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function getGoodsByCache($user, int $os, string $appName, int $platform, int $relatedType)
    {
        if ($user instanceof User) {
            $goods = $this->getUserCustomGoods($user, $os, $appName, $platform, $relatedType);
            if (!empty($goods)) return $goods;
        }

        $cacheKeyTemplate = $user instanceof User && $user->getRawOriginal('gender') == User::GENDER_WOMEN
        && $relatedType == Good::RELATED_TYPE_CARD ? config('redis_keys.cache.woman_goods_cache')
            : config('redis_keys.cache.goods_cache');

        $cacheKey      = sprintf($cacheKeyTemplate, Good::GOODS_TYPE_MAPPING[$relatedType]);
        $fieldParams   = [Good::CLIENT_OS_MAPPING[$os], $appName, Good::PLATFORM_MAPPING[$platform]];
        $fieldTemp     = '%s-%s-%s';
        $priceTestType = UserAb::TYPE_MEMBER_PRICE_TEST_A;
        if ( $relatedType == Good::RELATED_TYPE_CARD ) {
            $fieldTemp = '%s-%s-%s-%d';
            if ($user instanceof User && $platform != Good::PLATFORM_APPLE)
                $priceTestType = rep()->userAb->getMemberPriceTestType($user->id);
            $fieldParams[] = $priceTestType;
        }

        $field = sprintf($fieldTemp, ...$fieldParams);
        if (!Redis::hexists($cacheKey, $field)) {
            $lock = new RedisLock(Redis::connection(), 'lock:' . $cacheKey, 3);
            $lock->block(3, function () use (
                $cacheKey, $os, $appName,
                $relatedType, $platform, $field, $user, $priceTestType
            ) {
                if (!Redis::hexists($cacheKey, $field)) {
                    $cacheGoods = rep()->good->getGoodsByRelatedTypeAndOs($user, $os,
                        $appName, $platform, $relatedType, $priceTestType);
                    Redis::hset($cacheKey, $field, json_encode(array_values($cacheGoods)));
                }
            });
        }

        return json_decode(Redis::hget($cacheKey, $field), true);
    }

    /**
     * 获取用户定制的商品列表
     *
     * @param  \App\Models\User  $user
     * @param  int               $os
     * @param  string            $appName
     * @param  int               $platform
     * @param  int               $relatedType
     *
     * @return array|mixed
     */
    private function getUserCustomGoods($user, int $os, string $appName, int $platform, int $relatedType)
    {
        $exceptGoldTestUser = false;
        $exceptRelatedIds   = $exceptIds = $goods = [];
        if ($relatedType == Good::RELATED_TYPE_CARD && $user->getRawOriginal('gender')
            == User::GENDER_WOMEN) {
            $exceptRelatedIds = rep()->card->getQuery()->where('level', Card::LEVEL_HALF_MONTH)
                ->pluck('id')->toArray();
        } else {
            $exceptGoldTestUser = $relatedType == Good::RELATED_TYPE_CURRENCY
                && rep()->userAb->isExceptGoldTradeUser($user->id);
            $exceptGoldTestUser && $exceptRelatedIds = rep()->currency->getQuery()
                ->where('amount', 3000)->pluck('id')->toArray();
        }

        $switch = rep()->userSwitch->getQuery()->select('user_switch.status')
            ->join('switch', 'switch.id', 'user_switch.switch_id')->where('user_id', $user->id)
            ->where('switch.key', SwitchModel::KEY_CLOSE_WE_CHAT_TRADE)->first();
        if ($switch && $switch->status) {
            $exceptIds = rep()->good->getQuery()->whereIn('type',
                [Good::TYPE_WECHAT, Good::TYPE_WX_WAP])->pluck('id')->toArray();
        }

        if (!empty($exceptRelatedIds) || !empty($exceptIds)) {
            $priceTestType = UserAb::TYPE_MEMBER_PRICE_TEST_A;
            $relatedType == Good::RELATED_TYPE_CARD && $priceTestType
                = rep()->userAb->getMemberPriceTestType($user->id);

            $cacheGoods = rep()->good->getGoodsByRelatedTypeAndOs($user, $os, $appName, $platform,
                $relatedType, $priceTestType, $exceptRelatedIds, $exceptIds);
            $goods      = json_decode(json_encode(array_values($cacheGoods)), true);
            $exceptGoldTestUser && count($goods) && $goods[0]['is_default'] = 1;
        }

        return $goods;
    }

    /**
     * 获取代币支付的商品
     *
     * @param  int  $relatedType
     *
     * @return array
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function getProxyCurrencyGoodsByCache(int $relatedType)
    {
        $cacheKey = config('redis_keys.cache.proxy_currency_goods_cache');
        $field    = Good::GOODS_TYPE_MAPPING[$relatedType];
        if (!Redis::hexists($cacheKey, $field)) {
            $lock = new RedisLock(Redis::connection(), 'lock:' . $cacheKey, 3);
            $lock->block(3, function () use ($cacheKey, $relatedType, $field) {
                if (!Redis::hexists($cacheKey, $field)) {
                    $cacheGoods = rep()->good->getProxyCurrencyGoods($relatedType);
                    Redis::hset($cacheKey, $field, json_encode(array_values($cacheGoods)));
                }
            });
        }

        return json_decode(Redis::hget($cacheKey, $field), true);
    }

    /**
     * 根据客户端类型和会员卡级别获取非
     *
     * @param  int  $os
     * @param  int  $level
     *
     * @return array
     */
    public function getCardGoodByLevel(int $os, int $level)
    {
        $goods = rep()->good->getQuery()->select('product_id', 'goods.type', 'related_type', 'price',
            'is_default', 'uuid', 'related_id')->join('card', 'card.id', 'goods.related_id')
            ->where('goods.os', $os)->where('related_type', Good::RELATED_TYPE_CARD)->where('card.level',
                $level)->where('card.continuous', 0)
            ->get();

        $cacheGoods = rep()->good->buildInfoAndPayMethodToGoods($goods, Good::RELATED_TYPE_CARD);
        if ($cacheGoods) {
            return Arr::first($cacheGoods);
        }

        return (object)[];
    }

    /**
     * 判断是否打折
     *
     * @param  User|null   $user
     * @param  array|Good  $goods
     * @param  string      $os
     * @param  int         $type
     *
     * @return Good|array
     */
    public function judgeAndSetDiscount($user, &$goods, $os, int $type)
    {
        if ($type == Good::RELATED_TYPE_CARD) {
            if (!is_null($user)) {
                $discount = rep()->discount->getNotOverlapMinDiscount($user, $os);

                return $this->discount($user, $goods, $discount->discount, $discount);
            }
        }

        return $goods;
    }

    /**
     * 根据折扣修改商品内容
     *
     * @param  \App\Models\User  $user
     * @param  array|Good        $goods
     * @param  float             $discountRatio
     * @param  Discount|null     $discount
     *
     * @return Good|array
     */
    protected function discount($user, $goods, $discountRatio, $discount)
    {
        $useDiscounts = $abandonedDiscounts = [];
        if ($discountRatio > 0.5) {
            $overlapDiscounts = rep()->discount->getOverlapDiscounts($user);
            foreach ($overlapDiscounts as $overlapDiscount) {
                if ($discountRatio - $overlapDiscount->getRawOriginal('discount') < 0.499) {
                    break;
                }
                $discountRatio  -= $overlapDiscount->getRawOriginal('discount');
                $useDiscounts[] = $overlapDiscount->id;
            }
            $discount && $discount->id && $useDiscounts[] = $discount->id;
            $abandonedDiscounts = $overlapDiscounts->whereNotIn('id', $useDiscounts)
                ->pluck('id')->toArray();
        }

        if ($discountRatio < 1) {
            $discountRatio = (float)sprintf('%.2f', $discountRatio);
            if ($goods instanceof Good) {
                $goods->setAttribute('not_discount_price', $goods->getRawOriginal('price'));
                $goods->setRawOriginal('price', (int)floor($goods->price * $discountRatio) * 100);
                $goods->setAttribute('discount', $discountRatio);
                $goods->setAttribute('use_discounts', $useDiscounts);
                $goods->setAttribute('abandoned_discounts', $abandonedDiscounts);
            } elseif (is_array($goods) && !empty($goods)) {
                $title = sprintf(trans('messages.cut_percent_tmpl'), (1 - $discountRatio) * 100);

                foreach ($goods as $index => $good) {
                    $good['title']     = $title;
                    $good['ori_price'] = $good['price'];
                    $good['price']     = (int)floor($good['price'] * $discountRatio);
                    if ($good['related_type'] == Good::RELATED_TYPE_STR_CARD) {
                        $good['info']['extra']['average_price'] =
                            Card::getAveragePriceByLevelAndPrice($good['price'], $good['info']['level']);
                    }

                    $goods[$index] = $good;
                }
            }
        }

        return $goods;
    }
}

<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\Card;
use App\Models\Discount;
use App\Models\Good;

class GoodPocket extends BasePocket
{
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
                $title = sprintf('立减%.0f%%', (1 - $discountRatio) * 100);

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

<?php


namespace App\Pockets;


use App\Constant\ApiBusinessCode;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Good;
use App\Models\TradeBalance;
use App\Models\TradeModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TradeBalancePocket extends BasePocket
{
    /**
     * 创建用户代币相关对流水记录
     *
     * @param  User             $user
     * @param  TradeModel|Good  $related
     * @param  int              $amount
     * @param  int              $relatedType
     *
     * @return \App\Models\TradeBalance
     */
    public function createRecord($user, $related, int $amount = 0, int $relatedType = 0)
    {
        $tradeData = [
            'user_id'      => $user instanceof User ? $user->id : $user,
            'related_type' => $relatedType ?: $related->getRelatedType(),
            'related_id'   => $related->id,
            'amount'       => $amount ?: $related->getAmount(),
            'done_at'      => $related instanceof TradeModel ? $related->getDoneAt() : time(),
        ];

        return rep()->tradeBalance->getQuery()->create($tradeData);

    }

    /**
     * 代币购买商品
     *
     * @param  User  $user
     * @param  Good  $good
     *
     * @return \App\Foundation\Modules\ResultReturn\ResultReturn
     */
    public function buyMemberByProxyCurrency(User $user, Good $good)
    {
        $expiredAt = rep()->member->getUserMemberExpiredAt($user->id);

        return DB::transaction(function () use ($user, $good, $expiredAt) {
            $userWallet = rep()->wallet->getQuery()->lockForUpdate()->find($user->id);
            $goodAmount = $good->getRawOriginal('price');
            if ($userWallet->getRawOriginal('balance') < $goodAmount) {
                return ResultReturn::failed(trans('messages.lack_of_balance'),
                    ApiBusinessCode::LACK_OF_BALANCE);
            }

            $card      = rep()->card->getCardByGoodsId($good->id);
            $balance   = $this->createRecord($user, $good, -$goodAmount, TradeBalance::RELATED_TYPE_RECHARGE_VIP);
            $duration  = app()->environment('production') ? $card->getDuration($expiredAt) : 120;
            pocket()->member->createMemberByProxyCurrencyBuy($user, $balance, $good, $duration);
            pocket()->trade->createRecord($user, $balance);
            $userWallet->decrement('balance', $goodAmount);
            pocket()->inviteRecord->postBeInviterBuyMember($user, $card);

            return ResultReturn::success(null);
        });
    }
}

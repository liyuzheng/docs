<?php


namespace App\Repositories;

use App\Models\Card;
use App\Models\Discount;
use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Good;
use App\Models\InviteRecord;
use App\Models\TradePay;
use App\Models\User;

class DiscountRepository extends BaseRepository
{
    public function setModel()
    {
        return Discount::class;
    }

    /**
     * 通过邀请记录创建被邀请人的打折折扣记录
     *
     * @param  InviteRecord  $inviteRecord
     *
     * @return \App\Models\Discount
     */
    public function createBeInviterDiscountByRecord(InviteRecord $inviteRecord)
    {
        $clientVersion = user_agent()->clientVersion;
        if (!$clientVersion) {
            $userDetail    = rep()->userDetail->getQuery()->select('user_id', 'reg_version')
                ->where('user_id', $inviteRecord->target_user_id)->first();
            $clientVersion = $userDetail->getRawOriginal('reg_version');
        }

        $platform = version_compare($clientVersion, '2.2.0', '>=')
            ? Discount::PLATFORM_COMMON : Discount::PLATFORM_WEB;

        $discountData = [
            'related_type' => Discount::RELATED_TYPE_INVITE,
            'related_id'   => $inviteRecord->id,
            'user_id'      => $inviteRecord->target_user_id,
            'platform'     => $platform,
            'discount'     => Discount::APPLET_INVITE_TARGET_DISCOUNT,
        ];

        return $this->getQuery()->create($discountData);
    }

    /**
     * 获取用户折扣力度最大的一条诸折扣信息
     *
     * @param  User    $user
     * @param  string  $os
     *
     * @return \App\Models\Discount|null
     */
    public function getNotOverlapMinDiscount(User $user, string $os)
    {
        $discountOs    = Discount::PLATFORM_MAPPING[$os] ?? Discount::PLATFORM_ANDROID;
        $discountQuery = rep()->discount->getQuery()->select('id', 'related_type', 'related_id', 'discount',
            'expired_at')->where('user_id', $user->id)->where('type', Discount::TYPE_NOT_OVERLAP)
            ->where('done_at', 0)->whereIn('platform', [$discountOs, Discount::PLATFORM_COMMON])
            ->where(function ($query) {
                $query->where('expired_at', '>', time())->orWhere('expired_at', 0);
            })->orderBy('discount');

        $count = rep()->tradePay->getQuery()->where('user_id', $user->id)->where('related_type',
            TradePay::RELATED_TYPE_RECHARGE_VIP)->where('done_at', '!=', 0)->count();
        if ($count) {
            $discountQuery = $discountQuery->where('related_type', '!=',
                Discount::RELATED_TYPE_INVITE);
        }

        $discount = ($tmp = $discountQuery->first()) ?? new Discount(['discount' => 1]);
        if ($discountOs == Discount::PLATFORM_WEB && $discount->discount > Discount::TENCENT_CARD_DISCOUNT) {
            $discount->setAttribute('related_type', Discount::RELATED_TYPE_TENCENT)
                ->setAttribute('discount', Discount::TENCENT_CARD_DISCOUNT)
                ->setAttribute('expired_at', 0);
        }

        if (!$discount->discount || $discount->discount > Discount::NOT_CONTINUOUS_CARD_DISCOUNT) {
            $member   = rep()->member->getQuery()->select('member.user_id', 'member.start_at', 'member.duration',
                'card.level')->join('card', 'card.id', 'member.card_id')
                ->where('member.user_id', $user->id)->first();
            $expireAt = $member ? $member->getRawOriginal('start_at') + $member->getRawOriginal('duration') : 0;
            if ($expireAt > time() && $user->gender != User::GENDER_WOMEN
                && $member->getRawOriginal('level') != Card::LEVEL_FREE_VIP) {
                if ($expireAt < time() + Good::DISCOUNT_MINIMUM_SECONDS) {
                    $discount->setAttribute('related_type', Discount::RELATED_TYPE_RENEWAL)
                        ->setAttribute('discount', Discount::NOT_CONTINUOUS_CARD_DISCOUNT)
                        ->setAttribute('expired_at', 0);
                }
            }
        }

        return $discount;
    }

    /**
     * @param  User  $user
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverlapDiscounts(User $user)
    {
        return rep()->discount->getQuery()->select('id', 'related_type', 'related_id', 'discount')
            ->where('user_id', $user->id)->where('type', Discount::TYPE_CAN_OVERLAP)
            ->where('done_at', 0)->where(function ($query) {
                $query->where('expired_at', '>', time())->orWhere('expired_at', 0);
            })->orderBy('discount')->get();
    }

    /**
     * 获取用户可叠加使用的优惠卷总折扣
     *
     * @param  \App\Models\User|int  $user
     * @param  int                   $relatedType
     *
     * @return int|mixed
     */
    public function getOverlapDiscountSum($user, $relatedType = Discount::RELATED_TYPE_INVITE_PRIZE)
    {
        $userId = $user instanceof User ? $user->id : $user;

        return rep()->discount->getQuery()->where('user_id', $userId)
            ->when($relatedType, function ($query) use ($relatedType) {
                $query->where('related_type', $relatedType);
            })->where('type', Discount::TYPE_CAN_OVERLAP)
            ->sum('discount');
    }
}

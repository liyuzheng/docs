<?php

namespace App\Http\Controllers;

use App\Http\Requests\Trades\GoodsAndMemberRequest;
use App\Models\Card;
use App\Models\Discount;
use App\Models\Good;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class MemberController
 * @package App\Http\Controllers
 */
class MemberController extends BaseController
{
    /**
     * 获取用户会员信息
     *
     * @param  GoodsAndMemberRequest  $request
     * @param  int                    $uuid
     *
     * @return JsonResponse
     */
    public function member(GoodsAndMemberRequest $request, int $uuid)
    {
        $user   = rep()->user->getByUUid($uuid);
        $member = rep()->member->getUserValidMemberCard(optional($user)->id ?? 0);
        if ($member) {
            $os       = Good::CLIENT_OS_MAPPING[user_agent()->os];
            $platform = $request->has('platform') ? Good::PLATFORM_MAPPING[$request->platform]
                : Good::OS_DEFAULT_PLATFORM_MAPPING[$os];

            $expiredAt = $member->start_at + $member->duration;
            $format    = app()->environment('production') ? 'Y-m-d' : 'Y-m-d H:i:s';
            $member->setAttribute('expired_at', date($format, $expiredAt));
            $member->setAttribute('expired_at_ts', $expiredAt);
            $level = $member->card->level == Card::LEVEL_YEAR ? Card::LEVEL_MONTH : $member->card->level;

            /** @var Good|null $cardGood */
            [$cardGood, $card] = rep()->good->getSingleCardGoodsByLevel(
                user_agent()->appName, $os, $platform, $level);
            if ($cardGood) {
                // 判断需要打折
                if ($os == Good::CLIENT_OS_ANDROID && $expiredAt < time() + Good::DISCOUNT_MINIMUM_SECONDS
                    && !$member->continuous && $user->gender != User::GENDER_WOMEN
                    && $member->card->level != Card::LEVEL_FREE_VIP) {
                    $cardGood->setAttribute('discount_txt', trans('messages.renewal_member_time_limit_discount'));
                    $cardGood->setAttribute('price', (int)floor($cardGood->getRawOriginal('price') *
                            Discount::NOT_CONTINUOUS_CARD_DISCOUNT / 100) * 100);
                }

                $member->setAttribute('goods', $cardGood);
            }

            $member->card->setAttribute('uuid', $card->uuid);
        }

        !$member && $member = (object)[];

        return api_rr()->getOK($member);
    }

    /**
     * 会员续费状态 (打折折弹框)
     *
     * @param  int  $uuid
     *
     * @return JsonResponse
     */
    public function renewalStatus(int $uuid)
    {
        $user = rep()->user->getQuery()->where('uuid', $uuid)->first();
        if (!$user || user_agent()->appName == 'com.xm.xiaoquan.app') {
            return api_rr()->notFoundResult();
        }
        $member = rep()->member->getUserValidMemberCard($user->id);
        if (!$member) {
            return api_rr()->notFoundResult(trans('messages.member_not_exists_or_expire'));
        }

        $isNewVersion = version_compare(user_agent()->clientVersion, '2.2.0', '>=');
        $expiredAt    = $member->start_at + $member->duration;
        $residualDays = (int)ceil(($expiredAt - time()) / 86400);

        $data = [
            'button_txt'    => $isNewVersion ? trans('messages.immediately_renewal')
                : trans('messages.renewal_discount_tips'),
            'icon'          => cdn_url('uploads/common/' . ($isNewVersion
                    ? '20_percentage_discount.png' : 'eight_discount.png')),
            'alert_status'  => $member->card->level != Card::LEVEL_FREE_VIP &&
                $residualDays <= 3 && $user->gender != User::GENDER_WOMEN,
            'expired_at_ts' => $expiredAt,
            'txt_map'       => [
                [
                    'txt'   => sprintf(trans('messages.member_remain_days_tmpl'), $residualDays),
                    'is_br' => true
                ],
                ['txt' => trans('messages.renewal_discount_tmpl')]
            ],
        ];

        return api_rr()->getOK($data);
    }
}

<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Card;
use App\Models\Good;
use App\Models\Member;
use App\Models\MemberRecord;
use App\Models\Prize;
use App\Models\Role;
use App\Models\Task;
use App\Models\TradeBalance;
use App\Models\TradePay;
use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemberPocket extends BasePocket
{
    /**
     * 创建用户会员信息
     *
     * @param  User      $user
     * @param  TradePay  $tradePay
     * @param  string    $oriTradeNo
     * @param  int       $nextAt
     * @param  int       $duration
     * @param  int       $recordStatus
     *
     * @return \App\Models\Member
     */
    public function createMemberByCard(
        User $user, TradePay $tradePay, int $duration, $oriTradeNo = '',
        $nextAt = 0, $recordStatus = MemberRecord::STATUS_DEFAULT
    ) {
        $currentMember       = rep()->member->getAndLockOrCreateMember($user, $tradePay->related_id);
        $currentMemberRecord = $this->createUserMemberRecord($currentMember, $tradePay->id, $duration,
            $nextAt, $oriTradeNo, $recordStatus);
        $this->updateMemberByRecord($currentMember, $currentMemberRecord, $tradePay->related_id);

        return $currentMember;
    }

    /**
     * 创建代币购买的会员记录
     *
     * @param  User          $user
     * @param  TradeBalance  $balance
     * @param  Good          $good
     * @param  int           $duration
     *
     * @return Member
     */
    public function createMemberByProxyCurrencyBuy(User $user, TradeBalance $balance, Good $good, int $duration)
    {
        $currentMember       = rep()->member->getAndLockOrCreateMember($user, $good->related_id);
        $currentMemberRecord = $this->createUserMemberRecord($currentMember, $balance->id, $duration,
            0, '', MemberRecord::STATUS_DEFAULT, MemberRecord::TYPE_CURRENCY_BUY);
        $this->updateMemberByRecord($currentMember, $currentMemberRecord, $good->related_id);

        return $currentMember;
    }

    /**
     * 更新用户会员数据通过任务奖励
     *
     * @param  User        $user
     * @param  Collection  $tasks
     * @param  Card        $card
     *
     * @return Member
     */
    public function createMemberByTasks(User $user, Collection $tasks, Card $card)
    {
        $currentMember = rep()->member->getAndLockOrCreateMember($user, $card->id);
        $taskPrizes    = rep()->taskPrize->m()->select('task_prize.task_id', 'task_prize.value',
            'prize.type')->join('prize', 'prize.id', 'task_prize.prize_id')
            ->whereIn('task_id', $tasks->pluck('id')->toArray())->get();

        $currentNow       = time();
        $sumPrizeDuration = 0;
        $memberRecordArr  = [];
        $memberExpiredAt  = $currentMember->start_at + $currentMember->duration;
        $currentExpiredAt = $memberExpiredAt > $currentNow ? $memberExpiredAt : $currentNow;
        $timestamps       = ['created_at' => time(), 'updated_at' => time()];

        $recordFirstStartAt = $memberExpiredAt > time() ? $currentMember->start_at : time();
        foreach ($taskPrizes as $taskPrize) {
            $prizeDuration    = $taskPrize->getRawOriginal('value');
            $sumPrizeDuration += $prizeDuration;
            if ($prizeDuration > 0) {
                $memberRecordArr[] = array_merge([
                    'type'           => Prize::PRIZE_MEMBER_TYPE_MAPPING[$taskPrize->type],
                    'user_id'        => $user->id,
                    'pay_id'         => $taskPrize->task_id,
                    'first_start_at' => $recordFirstStartAt,
                    'duration'       => $prizeDuration,
                    'expired_at'     => $currentExpiredAt + $sumPrizeDuration,
                    'next_cycle_at'  => 0,
                ], $timestamps);
            }
        }

        rep()->memberRecord->m()->insert($memberRecordArr);
        if ($currentMember->start_at + $currentMember->duration < time()) {
            $memberUpdate = ['duration' => $sumPrizeDuration, 'start_at' => time(), 'card_id' => $card->id];
            $currentMember->setAttribute('duration', $sumPrizeDuration);
            $currentMember->setAttribute('start_at', $memberUpdate['start_at']);
        } else {
            $memberUpdate = ['duration' => DB::raw('duration + ' . $sumPrizeDuration)];
            $currentMember->setAttribute('duration', $currentMember->duration + $sumPrizeDuration);
        }

        $currentMember->update($memberUpdate);

        return $currentMember;
    }

    /**
     * 创建会员变更记录
     *
     * @param  Member  $member
     * @param  int     $payId
     * @param  int     $duration
     * @param          $nextAt
     * @param          $cert
     * @param          $status
     * @param          $type
     *
     * @return \App\Models\MemberRecord
     */
    public function createUserMemberRecord(Member $member, $payId, $duration, $nextAt, $cert, $status, $type = MemberRecord::TYPE_BUY)
    {
        $currentNow      = time();
        $memberExpiredAt = $member->start_at + $member->duration;
        $expiredAt       = ($memberExpiredAt > $currentNow ? $memberExpiredAt
                : $currentNow) + $duration;

        $maxNextAt = rep()->memberRecord->getQuery()->where('certificate', $cert)
            ->where('duration', $duration)->orderBy('next_cycle_at', 'desc')->first();
        if (!$maxNextAt || $nextAt > $maxNextAt->next_cycle_at) {
            rep()->memberRecord->getQuery()->where('certificate', $cert)
                ->where('duration', $duration)->update(['next_cycle_at' => 0]);
        } else {
            $nextAt = $maxNextAt->user_id != $member->user_id ? $nextAt : 0;
        }

        $memberRecordData = [
            'user_id'        => $member->user_id,
            'pay_id'         => $payId,
            'duration'       => $duration,
            'type'           => $type,
            'expired_at'     => $expiredAt,
            'first_start_at' => $memberExpiredAt > $currentNow
                ? $member->start_at : $currentNow,
            'next_cycle_at'  => $nextAt,
            'status'         => $status,
            'certificate'    => $cert
        ];

        return rep()->memberRecord->getQuery()->create($memberRecordData);
    }

    /**
     *
     * @param  User  $user
     */
    public function sendUserBecomeMemberMessage(User $user)
    {
        $message = trans('messages.role_to_vip_unlock_users', [], $user->language);
        $sender  = config('custom.little_helper_uuid');
        $user    = is_null($user->role) ? rep()->user->getQuery()->find($user->id) : $user;
        if (in_array(Role::KEY_CHARM_GIRL, explode(',', $user->role))) {
            $message = trans('messages.role_to_vip', [], $user->language);
        }

        try {
            pocket()->netease->msgSendMsg($sender, $user->uuid, $message);
        } catch (GuzzleException $exception) {
            Log::error($exception->getMessage(), ['target' => 'member.message']);
        }
    }

    /**
     * 取消用户会员信息通过第三方交易ID
     *
     * @param  array   $order
     * @param  string  $type
     */
    public function cancelMemberByOirTradeNo(array $order, string $type)
    {
        $tradeNo  = $order['web_order_line_item_id'] ?? $order['transaction_id'];
        $tradePay = rep()->tradePay->getQuery()->where('trade_no', $tradeNo)->first();

        if ($tradePay->getRawOriginal('related_type') == TradePay::RELATED_TYPE_RECHARGE_VIP) {
            DB::transaction(function () use ($order, $tradePay) {
                $currentNow   = time();
                $tradePay     = rep()->tradePay->getQuery()->lockForUpdate()->find($tradePay->id);
                $memberRecord = rep()->memberRecord->getQuery()->where('pay_id', $tradePay->id)
                    ->whereIn('status', MemberRecord::STATUS_VALID)->first();
                if ($memberRecord && $memberRecord->expired_at > $currentNow) {
                    $duration = $memberRecord->expired_at - $currentNow < $memberRecord->duration
                        ? $memberRecord->expired_at - $currentNow : $memberRecord->duration;
                    rep()->memberRecord->getQuery()->where('id', '>=', $memberRecord->id)
                        ->where('user_id', $memberRecord->user_id)->update([
                            'expired_at' => DB::raw('expired_at - ' . $duration),
                        ]);

                    $memberUpdateData = ['duration' => DB::raw('duration - ' . $duration)];
                    if ($memberRecord->next_cycle_at) {
                        $lastSameRecord = rep()->memberRecord->getQuery()->whereIn('status', MemberRecord::STATUS_VALID)
                            ->where('certificate', $memberRecord->certificate)->where('expired_at', '>', $currentNow)
                            ->where('duration', $memberRecord->duration)->where('id', '!=', $memberRecord->id)
                            ->orderBy('id', 'desc')->first();

                        if ($lastSameRecord) {
                            $lastSameRecord->update(['next_cycle_at' => $memberRecord->next_cycle_at]);
                        } else {
                            $count = rep()->memberRecord->getQuery()->where('user_id', $memberRecord->user_id)
                                ->where('id', '!=', $memberRecord->id)->whereIn('status', MemberRecord::STATUS_VALID)
                                ->where('next_cycle_at', '>', 0)->count();
                            if (!$count) {
                                $memberUpdateData['continuous'] = 0;
                            }
                        }
                    }

                    rep()->member->getQuery()->where('user_id', $memberRecord->user_id)
                        ->update($memberUpdateData);
                    $memberRecord->update([
                        'status'        => MemberRecord::STATUS_REFUND,
                        'next_cycle_at' => 0,
                        'duration'      => DB::raw('duration - ' . $duration),
                    ]);
                }
            });
        }
    }

    /**
     * 通过会员变更记录更新会员信息
     *
     * @param  Member        $member
     * @param  MemberRecord  $record
     * @param  int           $newCardId
     */
    public function updateMemberByRecord(Member $member, MemberRecord $record, int $newCardId)
    {
        $currentNow = time();
        $expiredAt  = $member->getRawOriginal('start_at') + $member->getRawOriginal('duration');

        $currentMemberUpdateData['duration'] = $expiredAt > $currentNow
            ? DB::raw('duration + ' . $record->duration) : $record->duration;

        $record->next_cycle_at && $currentMemberUpdateData['continuous'] = 1;
        $expiredAt < $currentNow && $currentMemberUpdateData['start_at'] = $currentNow;

        if ($newCardId != $member->getRawOriginal('card_id')) {
            $cards = rep()->card->getQuery()->whereIn('id', [$member->card_id, $newCardId])->get();
            if (Card::CARD_LEVEL_SCORE[$cards->find($newCardId)->level] >
                Card::CARD_LEVEL_SCORE[$cards->find($member->card_id)->level]
                || $expiredAt < $currentNow) {
                $currentMemberUpdateData['card_id'] = $newCardId;
            }
        }

        $member->update($currentMemberUpdateData);
    }


    /**
     * 继承会员更新记录
     *
     * @param  User          $user
     * @param  MemberRecord  $record
     * @param  Member        $oriMember
     * @param  int           $duration
     * @param  int           $cardId
     */
    public function inheritMemberRecord(User $user, MemberRecord $record, Member $oriMember, int $duration, int $cardId)
    {
        $currentMember = rep()->member->getAndLockOrCreateMember($user, $cardId);

        $currentMemberRecord = $this->createUserMemberRecord($currentMember, $record->pay_id, $duration,
            $record->next_cycle_at, $record->certificate, MemberRecord::STATUS_INHERITED);
        $this->updateMemberByRecord($currentMember, $currentMemberRecord, $cardId);

        $hasResidualContinuousRecord = rep()->memberRecord->getQuery()->where('user_id', $record->user_id)
            ->where('id', '>', $record->id)->whereIn('status', MemberRecord::STATUS_VALID)
            ->where('next_cycle_at', '>', 0)->count();
        $originalMemberUpdateData    = ['duration' => DB::raw('duration - ' . $duration)];
        if (!$hasResidualContinuousRecord) {
            $originalMemberUpdateData['continuous'] = 0;
        }

        $oriMember->update($originalMemberUpdateData);
        rep()->memberRecord->getQuery()->where('user_id', $record->user_id)->where('id', '>', $record->id)
            ->update(['expired_at' => DB::raw('expired_at - ' . $duration)]);
        $record->update([
            'next_cycle_at' => 0,
            'status'        => MemberRecord::STATUS_BE_INHERITED,
            'duration'      => DB::raw('duration - ' . $duration),
            'expired_at'    => DB::raw('expired_at - ' . $duration),
        ]);
    }

    /**
     * 判断用户当前是否会员
     *
     * @param $userId
     *
     * @return bool
     */
    public function userIsMember($userId)
    {
        $userMember = rep()->member->getQuery()->where('user_id', $userId)->first();

        return $userMember && $userMember->start_at + $userMember->duration > time();
    }
}

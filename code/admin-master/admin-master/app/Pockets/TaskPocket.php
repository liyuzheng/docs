<?php


namespace App\Pockets;


use App\Models\Card;
use App\Models\Discount;
use App\Models\Prize;
use App\Models\TradePay;
use App\Models\User;
use App\Models\Task;
use App\Models\InviteRecord;
use App\Models\TradeIncome;
use App\Models\UserAb;
use Illuminate\Support\Facades\DB;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class TaskPocket extends BasePocket
{

    /**
     * 用户邀请普通用户注册，创建邀请记录 app 途径的邀请人如果是男用户获得会员奖励
     *
     * @param  User  $inviter
     * @param  User  $beInviter
     * @param  int   $channel
     *
     * @return mixed
     */
    public function completeTaskBeInviterRegister(User $inviter, User $beInviter, int $channel)
    {
        pocket()->common->commonQueueMoreByPocketJob(pocket()->stat, 'incrInviteUserReg',
            [$beInviter->id, $beInviter->getRawOriginal('created_at')], 10);

        return DB::transaction(function () use ($inviter, $beInviter, $channel) {
            $record = rep()->inviteRecord->createInviteRecord($inviter, $beInviter,
                InviteRecord::TYPE_USER_REG, $channel);
            rep()->userDetail->m()->where('user_id', $beInviter->id)->update(['inviter' => $inviter->id]);
            rep()->userDetail->m()->where('user_id', $inviter->id)->increment('invite_count');
            rep()->userAb->updateUserInviteAbTestByInvite($inviter, $beInviter);

            $inviteChannel = $record->getRawOriginal('channel');
            if ($inviteChannel == InviteRecord::CHANNEL_APP
                && $inviter->getOriginal('gender') == User::GENDER_MAN) {
                $inviterTestRecord = rep()->userAb->getUserInviteTestRecord($inviter);
                $inviterTestIsB    = $inviterTestRecord && $inviterTestRecord->inviteTestIsB();
                $prizeType         = $inviterTestIsB ? Prize::TYPE_CUMULATIVE_DISCOUNT : Prize::TYPE_MAN_INVITE;
                if (pocket()->inviteRecord->isInvitePunishment($inviter->id)) {
                    $prizeType = Prize::TYPE_PUNISHMENT_MEMBER;
                }
                $prize = rep()->prize->getPrizeByType($prizeType);

                $taskRelatedType = $inviterTestIsB ? Task::RELATED_TYPE_MEMBER_DISCOUNT
                    : Task::RELATED_TYPE_MAN_INVITE_REG;
                $this->createUserTaskRewards($inviter, $prize, $record,
                    $taskRelatedType);
            } elseif ($inviteChannel == InviteRecord::CHANNEL_APPLET) {
                rep()->discount->createBeInviterDiscountByRecord($record);
            }

            return $record;
        });
    }

    /**
     * 被邀请用户成为会员, 邀请人获得奖励
     *
     * @param  User  $inviter
     * @param  User  $beInviter
     * @param  Card  $card
     * @param  int   $channel
     *
     * @return mixed
     */
    public function completeTaskBeInviterMember(User $inviter, User $beInviter, Card $card, int $channel)
    {
        if ($channel == InviteRecord::CHANNEL_APPLET) {
            $prizeType   = Prize::MEMBER_LEVEL_TYPE_MAPPING[$card->getRawOriginal('level')];
            $relatedType = Task::RELATED_TYPE_APPLET_INVITE_MEMBER;
        } else {
            $inviterIsMan = $inviter->getRawOriginal('gender') == User::GENDER_MAN;
            $prizeType    = $inviterIsMan ? Prize::TYPE_MAN_INVITE_MEMBER
                : Prize::TYPE_WOMAN_INVITE_MEMBER;
            if (pocket()->inviteRecord->isInvitePunishment($inviter->id)) {
                $prizeType = Prize::TYPE_PUNISHMENT_MEMBER;
            }
            $relatedType = $inviterIsMan ? Task::RELATED_TYPE_MAN_INVITE_MEMBER
                : Task::RELATED_TYPE_WOMAN_INVITE_MEMBER;
        }

        $prize = rep()->prize->getPrizeByType($prizeType);

        return DB::transaction(function () use ($inviter, $beInviter, $channel, $prize, $relatedType) {
            $record = rep()->inviteRecord->createInviteRecord($inviter, $beInviter,
                InviteRecord::TYPE_USER_MEMBER, $channel);
            $this->createUserTaskRewards($inviter, $prize, $record, $relatedType);

            return $record;
        });
    }

    /**
     * 创建邀请人任务奖励
     *
     * @param  User          $inviter
     * @param  Prize         $prize
     * @param  InviteRecord  $inviteRecord
     * @param  int           $relatedType
     */
    public function createUserTaskRewards(User $inviter, Prize $prize, InviteRecord $inviteRecord, int $relatedType)
    {
        $rewardContent  = $prize->getRawOriginal('value');
        $createTaskData = [
            'related_type' => $relatedType,
            'related_id'   => $inviteRecord->id,
            'user_id'      => $inviter->id,
            'status'       => Task::STATUS_DEFAULT
        ];

        $prizeRelatedType = $prize->getRawOriginal('related_type');
        if ($prizeRelatedType == Prize::RELATED_TYPE_CASH) {
            $createTaskData = array_merge($createTaskData,
                ['status' => Task::STATUS_SUCCEED, 'done_at' => time()]);
        }
        $task          = rep()->task->getQuery()->create($createTaskData);
        $taskPrizeData =
            ['task_id' => $task->id, 'prize_id' => $prize->id, 'value' => $rewardContent];

        rep()->taskPrize->getQuery()->create($taskPrizeData);
        if ($prize->getRawOriginal('type') != Prize::TYPE_PUNISHMENT_MEMBER) {
            $this->createTaskAttachedData($inviter, $prize, $task);
        }
    }

    /**
     * 创建邀请人任务相关数据
     *
     * @param  User   $inviter
     * @param  Prize  $prize
     * @param  Task   $task
     */
    protected function createTaskAttachedData(User $inviter, Prize $prize, Task $task)
    {
        $rewardContent      = $prize->getRawOriginal('value');
        $prizeRelatedType   = $prize->getRawOriginal('related_type');
        $walletUpdateFields = ['free_vip', 'free_vip_total'];

        if ($prizeRelatedType == Prize::RELATED_TYPE_CASH) {
            $walletUpdateFields = ['income_invite', 'income_invite_total'];
            $tradeIncomeData    = [
                'user_id'      => $inviter->id,
                'related_type' => TradeIncome::RELATED_TYPE_INVITE_USER,
                'related_id'   => $task->getRawOriginal('related_id'),
                'amount'       => $rewardContent,
                'done_at'      => time(),
            ];

            $tradeIncome = rep()->tradeIncome->getQuery()->create($tradeIncomeData);
            pocket()->trade->createRecord($inviter, $tradeIncome);
        } elseif ($prizeRelatedType == Prize::RELATED_TYPE_DISCOUNT) {
            $walletUpdateFields = [];
            $discountData       = [
                'related_type' => Discount::RELATED_TYPE_INVITE_PRIZE,
                'related_id'   => $task->id,
                'user_id'      => $inviter->id,
                'platform'     => Discount::PLATFORM_COMMON,
                'discount'     => $rewardContent / 100,
                'type'         => Discount::TYPE_CAN_OVERLAP,
            ];

            rep()->discount->getQuery()->create($discountData);
        }

        $updateData = [];
        foreach ($walletUpdateFields as $walletUpdateField) {
            $updateData[$walletUpdateField] = DB::raw($walletUpdateField . ' + ' . $rewardContent);
        }

        $updateData && rep()->wallet->m()
            ->where('user_id', $inviter->id)->update($updateData);
    }

    /**
     * 老版被邀请人注册 添加邀请记录 邀请人如果是男用户获得会员奖励
     *
     * @param  int  $inviterId
     * @param  int  $beInviterId
     * @param  int  $channel
     *
     * @return ResultReturn
     */
    public function postTaskInviteRegister(int $inviterId, int $beInviterId, int $channel = InviteRecord::CHANNEL_APP)
    {
        [$inviter, $beInviter] = $this->getInviteUsersAndDetailByIds($inviterId, $beInviterId);
        $userDetail = rep()->userDetail->getQuery()->where('user_id', $beInviter->id)->first();
        if ($userDetail->inviter != $inviter->id) {
            return ResultReturn::failed('用户已经被其他用户邀请注册啦');
        }

        $latestMemberRecord = rep()->inviteRecord
            ->getLatestInviteUserMemberByUserId($inviter->id, $beInviter->id);
        if ($latestMemberRecord) {
            return ResultReturn::failed('不能重复享受邀请会员福利');
        }

        $inviteRecord = $this->completeTaskBeInviterRegister($inviter, $beInviter, $channel);

        return ResultReturn::success($inviteRecord);
    }

    /**
     * 老版被邀请人成为会员 邀请人获得奖励逻辑
     *
     * @param  int  $inviterId
     * @param  int  $beInviterId
     *
     * @return ResultReturn
     */
    public function postTaskInviteMember(int $inviterId, int $beInviterId)
    {
        [$inviter, $beInviter] = $this->getInviteUsersAndDetailByIds($inviterId, $beInviterId);
        $registerInviteRecord = rep()->inviteRecord->getQuery()->where('user_id', $inviter->id)
            ->where('type', InviteRecord::TYPE_USER_REG)
            ->where('target_user_id', $beInviter->id)->first();
        if (!$registerInviteRecord) {
            return ResultReturn::failed('不是你邀请注册的用户');
        }

        $latestMemberRecord = rep()->inviteRecord
            ->getLatestInviteUserMemberByUserId($inviter->id, $beInviter->id);
        if ($latestMemberRecord) {
            return ResultReturn::failed('不能重复享受邀请会员福利');
        }

        $card = rep()->card->getQuery()->select('card.*')->join('trade_pay as tp',
            'tp.related_id', 'card.id')->where('tp.user_id', $beInviter->id)
            ->where('tp.related_type', TradePay::RELATED_TYPE_RECHARGE_VIP)
            ->where('tp.done_at', '>', 0)->orderBy('tp.id')->first();

        $inviteTestRecord = rep()->userAb->getUserInviteTestRecord($inviter);
        if ($inviteTestRecord && $inviteTestRecord->inviteTestIsB()) {
            return ResultReturn::failed('折扣邀请没有邀请会员福利');
        }

        $inviteRecord = $this->completeTaskBeInviterMember($inviter, $beInviter, $card,
            $registerInviteRecord->channel ?: InviteRecord::CHANNEL_APP);

        return ResultReturn::success($inviteRecord);
    }

    /**
     * 获取邀请人 和 被邀请人 User 对象
     *
     * @param  int  $inviterId
     * @param  int  $beInviterId
     *
     * @return array
     */
    private function getInviteUsersAndDetailByIds(int $inviterId, int $beInviterId)
    {
        $users     = rep()->user->getQuery()->whereIn('id', [$inviterId, $beInviterId])->get();
        $inviter   = $users->where('id', $inviterId)->first();
        $beInviter = $users->where('id', $beInviterId)->first();

        return [$inviter, $beInviter];
    }
}

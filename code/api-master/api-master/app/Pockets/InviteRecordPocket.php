<?php


namespace App\Pockets;

use App\Constant\ApiBusinessCode;
use App\Models\Card;
use App\Models\InviteBuildRecord;
use App\Models\InviteRecord;
use App\Models\Prize;
use App\Models\Task;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use Illuminate\Support\Facades\Redis;

class InviteRecordPocket extends BasePocket
{
    /**
     * 邀请的用户购买会员
     *
     * @param  User  $beInviter
     * @param  Card  $card
     * @param  bool  $isQueue
     *
     * @return ResultReturn
     */
    public function postBeInviterBuyMember(User $beInviter, Card $card, bool $isQueue = false)
    {
        $userDetail = rep()->userDetail->getQuery()->where('user_id', $beInviter->id)->first();
        if ($isQueue) {
            pocket()->common->commonQueueMoreByPocketJob(pocket()->task, 'postTaskInviteMember',
                [$userDetail->inviter, $beInviter->id]);

            return ResultReturn::success([], 'queue');
        }

        if ($userDetail->inviter) {
            $inviter = rep()->user->getQuery()->find($userDetail->inviter);
            $records = rep()->inviteRecord->getQuery()->where('user_id', $inviter->id)
                ->whereIn('type', [InviteRecord::TYPE_USER_MEMBER, InviteRecord::TYPE_USER_REG])
                ->where('target_user_id', $beInviter->id)->get();

            if ($records->where('type', InviteRecord::TYPE_USER_MEMBER)->count()) {
                return ResultReturn::failed(trans('messages.not_repeat_reward'));
            }

            $registerRecord = $records->where('type', InviteRecord::TYPE_USER_REG)->first();
            if (!$registerRecord) {
                return ResultReturn::failed(trans('messages.not_mine_invite_users'));
            }

            $inviteTestRecord = rep()->userAb->getUserInviteTestRecord($inviter);
            if ($inviteTestRecord && $inviteTestRecord->inviteTestIsB()) {
                return ResultReturn::failed(trans('messages.discount_invite_vip_not_reward'));
            }
            $record = pocket()->task->completeTaskBeInviterMember($inviter, $beInviter,
                $card, $registerRecord->channel);

            return ResultReturn::success($record);
        }

        return ResultReturn::failed(trans('messages.rechage_not_inviter'));
    }

    /**
     * 用户邀请注册用户
     *
     * @param  User  $inviter
     * @param  User  $beInviter
     * @param  int   $channel
     * @param  bool  $isQueue
     *
     * @return ResultReturn
     */
    public function postBeInviterRegister(User $inviter, User $beInviter, int $channel, $isQueue = false)
    {
        if ($isQueue) {
            pocket()->common->commonQueueMoreByPocketJob(pocket()->task, 'postTaskInviteRegister',
                [$inviter->id, $beInviter->id]);

            return ResultReturn::success([], 'queue');
        }

        $record = rep()->inviteRecord->getQuery()->where('type', InviteRecord::TYPE_USER_REG)
            ->where('target_user_id', $beInviter->id)->first();

        if ($record && $record->user_id == $inviter->id) {
            return ResultReturn::failed(trans('messages.not_repeat_reward'));
        } elseif ($record && $record->user_id != $inviter->id) {
            return ResultReturn::failed(trans('messages.repeated_invite_notice'));
        }

        $record = pocket()->task->completeTaskBeInviterRegister($inviter, $beInviter, $channel);

        return ResultReturn::success($record);
    }

    /**
     * 领取邀请奖励的会员
     *
     * @param  User        $user
     * @param  Collection  $tasks
     *
     * @return ResultReturn
     */
    public function postTaskInviteMemberByUser(User $user, Collection $tasks)
    {
        $card = rep()->card->getFreeMemberCard();

        return DB::transaction(function () use ($user, $tasks, $card) {
            $tasks = rep()->task->m()->whereIn('id', $tasks->pluck('id')->toArray())
                ->where('done_at', 0)->where('status', Task::STATUS_DEFAULT)
                ->lockForUpdate()->get();
            if (!$tasks->count()) {
                return ResultReturn::failed(trans('messages.not_available_reward'),
                    ApiBusinessCode::FORBID_COMMON);
            }

            $member  = pocket()->member->createMemberByTasks($user, $tasks, $card);
            $updates = ['status' => Task::STATUS_SUCCEED, 'done_at' => time()];
            rep()->task->m()->whereIn('id', $tasks->pluck('id')->toArray())->update($updates);
            rep()->wallet->m()->where('user_id', $user->id)
                ->update(['free_vip' => 0]);

            return ResultReturn::success($member);
        });
    }

    /**
     * 获取邀请收益头部信息
     *
     * @param  User        $user
     * @param  UserDetail  $userDetail
     * @param  int         $channel
     *
     * @return array
     */
    public function getIncomeRecords(User $user, UserDetail $userDetail, int $channel)
    {
        $wallet = rep()->wallet->getByUserId($user->id);
        if ($channel == InviteRecord::CHANNEL_APPLET) {
            $parameters = $wallet->get('income_invite_total', 'income_invite');
            $parameters = array_merge($parameters, [
                'invite_url'   => pocket()->config->getMasterInviteUrl($userDetail->invite_code),
                'forever_url'  => pocket()->config->getForeverInviteUrl($userDetail->invite_code),
                'invite_count' => $userDetail->invite_count
            ]);


            return $parameters;
        }

        $user = is_null($user->gender) ? rep()->user->getById($user->id) : $user;
        if ($user->getRawOriginal('gender') == User::GENDER_MAN) {
            $welfare     = [
                ['title' => trans('messages.invited_count'), 'value' => $userDetail->invite_count],
                ['title' => trans('messages.receive_vip_days_tmpl'), 'value' => $wallet->free_vip],
                ['title' => trans('messages.all_vip_day_tmpl'), 'value' => $wallet->free_vip_total],
            ];
            $receiveType = 'member';
            $rules       = [
                trans('messages.right_code_success'),
                pocket()->inviteRecord->getAboutInviteTextByUser($user)['rule_two'],
                pocket()->inviteRecord->getAboutInviteTextByUser($user)['rule_three']
            ];
        } else {
            $welfare     = [
                ['title' => trans('messages.all_earn_teml'), 'value' => $wallet->income_invite_total],
                ['title' => trans('messages.withdraw_reward_teml'), 'value' => $wallet->income_invite],
                ['title' => trans('messages.all_invited_count_teml'), 'value' => $userDetail->invite_count],
            ];
            $receiveType = 'withdraw';
            $rules       = [
                trans('messages.right_code_success'),
                trans('messages.invte_user_to_vip_reward'),
                trans('messages.invite_give_reword')
            ];
        }

        return [$welfare, $receiveType, $rules];
    }

    /**
     * 获取邀请用户详情并绑定收益
     *
     * @param  User|array  $user
     * @param  Collection  $inviteRecords
     *
     * @return Collection
     */
    public function getInviteUsersAndBuildReward($user, $inviteRecords)
    {
        $inviteUserIds = $inviteRecordInfos = [];
        foreach ($inviteRecords as $inviteRecord) {
            $userId                     = $inviteRecord->target_user_id;
            $inviteUserIds[]            = $userId;
            $inviteRecordInfos[$userId] = $inviteRecord;
        }

        $userIds             = $user instanceof User ? [$user->id] : (is_array($user) ? $user : [$user]);
        $memberInviteRecords = rep()->inviteRecord->getQuery()->select('invite_record.target_user_id',
            'tp.value', 'prize.related_type')->join('task', 'task.related_id', 'invite_record.id')
            ->join('task_prize as tp', 'tp.task_id', 'task.id')->join('prize', 'prize.id', 'tp.prize_id')
            ->whereIn('invite_record.user_id', $userIds)->whereIn('invite_record.target_user_id',
                $inviteUserIds)->whereIn('task.related_type', [
                Task::RELATED_TYPE_MAN_INVITE_MEMBER,
                Task::RELATED_TYPE_WOMAN_INVITE_MEMBER,
                Task::RELATED_TYPE_APPLET_INVITE_MEMBER,
            ])->get();

        $userRewardInfos = [];
        foreach ($memberInviteRecords as $memberInviteRecord) {
            $cashValue = $memberInviteRecord->getRawOriginal('related_type') == Prize::RELATED_TYPE_CASH
                ? $memberInviteRecord->value / 100 : 0;

            $userRewardInfos[$memberInviteRecord->target_user_id] =
                ['type' => 'cash', 'value' => $cashValue,];
        }

        $inviteUsers = rep()->user->m()
            ->select(['id', 'uuid', 'nickname', 'gender', 'birthday'])
            ->whereIn('id', $inviteUserIds)->when(!empty($orderBy), function ($query) use ($inviteUserIds) {
                $query->orderByRaw(DB::raw('FIELD(id, ' . implode(',', $inviteUserIds) . ')'));
            })->get();

        pocket()->user->appendToUsers($inviteUsers, ['avatar']);
        pocket()->user->appendHasPayMemberToUsers($inviteUsers);

        foreach ($inviteUsers as $inviteUser) {
            $inviteUser->setAttribute('event_at',
                substr(date('Y/m/d', $inviteRecordInfos[$inviteUser->id]->created_at->timestamp), 2));
            if (isset($userRewardInfos[$inviteUser->id])) {
                $inviteUser->setAttribute('reward', $userRewardInfos[$inviteUser->id]);
            }
        }

        return $inviteUsers;
    }

    /**
     * 创建邀请绑定手机号的记录
     *
     * @param  int|string  $certificate
     * @param  string      $authFiled
     * @param  int         $inviteCode
     *
     * @return ResultReturn
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function postInviteBuildRecord($certificate, $authFiled, $inviteCode)
    {
        if (rep()->user->getQuery()->where($authFiled, $certificate)
            ->where('destroy_at', 0)->count()) {
            return ResultReturn::failed('当前用户已经注册成小圈用户啦, 不能再绑定邀请码啦');
        }

        $lockKey  = sprintf('lock:build_invite:%s', $certificate);
        $hasQuery = rep()->inviteBuildRecord->getQuery()
            ->where('related_type', InviteBuildRecord::RELATED_TYPE_MOBILE_OR_EMAIL)
            ->where('content', $certificate);

        if (!(clone $hasQuery)->count()) {
            $lock = new RedisLock(Redis::connection(), $lockKey, 3);

            return $lock->block(3, function () use ($certificate, $inviteCode, $hasQuery) {
                if (!$hasQuery->count()) {
                    $buildCreateData = [
                        'channel'      => InviteBuildRecord::CHANNEL_APPLET,
                        'related_type' => InviteBuildRecord::RELATED_TYPE_MOBILE_OR_EMAIL,
                        'user_id'      => rep()->userDetail->getUserIdByInviteCode((int)$inviteCode),
                        'content'      => $certificate
                    ];

                    $record = rep()->inviteBuildRecord->getQuery()->create($buildCreateData);

                    return ResultReturn::success($record);
                }

                return ResultReturn::failed('当前账号已经绑定过邀请人啦, 不能再绑定啦~');
            });
        }

        return ResultReturn::failed('当前账号已经绑定过邀请人啦, 不能再绑定啦~');
    }

    /**
     * 检查当前注册的用户手机是否绑定过邀请记录, 并生产相应的记录
     *
     * @param  User    $beInviter
     * @param  string  $authField
     *
     * @return ResultReturn
     */
    public function checkAndBindUserInviteRecord(User $beInviter, string $authField)
    {
        $buildRecord = rep()->inviteBuildRecord->getQuery()->where('related_type',
            InviteBuildRecord::RELATED_TYPE_MOBILE_OR_EMAIL)->where('content', $beInviter->{$authField})
            ->where('invite_id', 0)->first();
        if ($buildRecord && !$buildRecord->invite_id) {
            $inviter    = rep()->user->getQuery()->find($buildRecord->user_id);
            $inviteResp = $this->postBeInviterRegister($inviter, $beInviter, $buildRecord->channel);
            if ($inviteResp->getStatus()) {
                $buildRecord->update(['invite_id' => $inviteResp->getData()->id]);
            }

            return $inviteResp;
        }

        return ResultReturn::failed('没有绑定邀请记录或已被其他人邀请');
    }

    /**
     * 检查用户是否是小程序邀请的
     *
     * @param  User  $beInviter
     *
     * @return bool
     */
    public function checkUserIsAppletInvite(User $beInviter)
    {
        $record = rep()->inviteRecord->getQuery()->where('target_user_id', $beInviter->id)
            ->where('type', InviteRecord::TYPE_USER_REG)->first();
        if ($record) {
            return $record->channel == InviteRecord::CHANNEL_APPLET;
        }

        return false;
    }

    /**
     * 判断用户是否正在接受邀请惩罚
     *
     * @param  int  $userId
     *
     * @return bool
     */
    public function isInvitePunishment($userId)
    {
        $now       = time();
        $userMongo = mongodb('user_info')->where('_id', $userId)->first();
        if ($userMongo) {
            if (key_exists('mark', $userMongo) && $userMongo['mark'] != 0) {
                if ($userMongo['mark'] >= 3) {
                    return true;
                }

                $userLatestPunishment = rep()->memberPunishment->m()
                    ->where('user_id', $userId)->orderByDesc('id')
                    ->first();
                if ($userLatestPunishment) {
                    $expired_at = key_exists('expired_at', $userMongo)
                        ? $userMongo['expired_at'] : 0;

                    return (boolean)($expired_at > $now);
                }
            }
        }

        return false;
    }

    /**
     * 获得邀请相关的Text文案
     *
     * @param  User  $user
     *
     * @return string[]
     */
    public function getAboutInviteTextByUser(User $user) : array
    {
        if ($user->getOriginal('gender') == User::GENDER_MAN) {
            return [
                'rule_one'   => '每邀请一位注册用户,注册时正确输入您的专属邀请码即代码邀请成功;',
                'rule_two'   => '被邀请用户充值成为VIP后，您即可领取5天VIP奖励！',
                'rule_three' => '最终解释权归本平台所有;',
            ];
        } else {
            return [
                'rule_one'   => '每邀请一位注册用户,注册时正确输入您的专属邀请码即代码邀请成功;',
                'rule_two'   => '邀请一位注册用户赠送1天会员, 邀请的用户成为付费会员时再赠送2天会员;',
                'rule_three' => '最终解释权归本平台所有;',
            ];
        }
    }
}

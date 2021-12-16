<?php


namespace App\Pockets;

use App\Models\Prize;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Foundation\Modules\Pocket\BasePocket;

class InviteRecordPocket extends BasePocket
{
    /**
     * 获取邀请用户详情并绑定收益
     *
     * @param  User|array  $user
     * @param  Collection  $inviteRecords
     *
     * @return Collection
     */
    public function getInviteUsersAndBuildReward($user, Collection $inviteRecords) : Collection
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
     * 判断用户是否正在接受邀请惩罚
     *
     * @param  int  $userId
     *
     * @return bool
     */
    public function isInvitePunishment(int $userId) : bool
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
}

<?php


namespace App\Repositories;


use App\Models\InviteRecord;
use App\Models\MemberRecord;
use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Task;

class MemberRecordRepository extends BaseRepository
{
    public function setModel()
    {
        return MemberRecord::class;
    }

    /**
     * 获得用户最后一条会员记录
     *
     * @param  int  $userId
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getUserLatestMemberRecord(int $userId)
    {
        return rep()->memberRecord->getQuery()
            ->where('user_id', $userId)->where('expired_at', '>', time())
            ->orderBy('id', 'desc')->first();
    }


    /**
     * 获得用户邀请的某个用户的最新一条会员记录
     *
     * @param  int  $userId
     * @param  int  $targetUserId
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function whetherInviteMemberRecordByUserId(int $userId, int $targetUserId)
    {
        return rep()->task->m()
            ->whereIn('type', [Task::RELATED_TYPE_MAN_INVITE_MEMBER, Task::RELATED_TYPE_WOMAN_INVITE_MEMBER])
            ->where('user_id', $userId)
            ->where('target_user_id', $targetUserId)
            ->orderBy('id', 'desc')
            ->first();
    }
}

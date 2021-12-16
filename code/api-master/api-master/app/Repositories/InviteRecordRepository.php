<?php


namespace App\Repositories;


use App\Models\InviteRecord;
use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\User;

class InviteRecordRepository extends BaseRepository
{
    public function setModel()
    {
        return InviteRecord::class;
    }

    /**
     * 获得用户邀请成为会员的最后一条记录
     *
     * @param  int  $userId
     * @param  int  $targetUserId
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getLatestInviteUserMemberByUserId(int $userId, int $targetUserId)
    {
        return rep()->inviteRecord->m()
            ->where('type', InviteRecord::TYPE_USER_MEMBER)
            ->where('user_id', $userId)
            ->where('target_user_id', $targetUserId)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * 创建邀请记录
     *
     * @param  User  $inviter
     * @param  User  $beInviter
     * @param  int   $type
     * @param  null  $channel
     *
     * @return \App\Models\InviteRecord
     */
    public function createInviteRecord(User $inviter, User $beInviter, $type, $channel = null)
    {
        $channel          = $channel ?? InviteRecord::CHANNEL_APP;
        $inviteRecordData = [
            'channel'        => $channel,
            'type'           => $type,
            'user_id'        => $inviter->id,
            'target_user_id' => $beInviter->id,
            'status'         => InviteRecord::STATUS_SUCCEED,
            'done_at'        => time(),
        ];

        return rep()->inviteRecord->getQuery()->create($inviteRecordData);
    }
}

<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Member;
use App\Models\MemberRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MemberRepository extends BaseRepository
{
    public function setModel()
    {
        return Member::class;
    }

    /**
     * 通过 userId 获取到用户当前正在生效到会员卡信息
     *
     * @param  int  $userId
     *
     * @return \App\Models\Member
     */
    public function getUserValidMemberCard(int $userId)
    {
        $member = $this->getQuery()->select('card_id', 'continuous', 'start_at', 'duration')
            ->with([
                'card' => function ($query) {
                    $query->select('id', 'uuid', 'name', 'level', 'extra');
                }
            ])->where('user_id', $userId)->first();

        return $member && $member->start_at + $member->duration > time()
            ? $member : null;
    }

    /**
     * 通过 userId 获取到用户当前正在生效到会员卡信息
     *
     * @param  int  $userId
     *
     * @return \App\Models\Member
     */
    public function getUserValidMember(int $userId)
    {
        $member = $this->getQuery()
            ->select('card_id', 'continuous', 'start_at', 'duration')
            ->where('user_id', $userId)
            ->first();

        return $member && $member->start_at + $member->duration > time()
            ? $member : null;
    }

    /**
     * 获得最新member
     *
     * @param  int  $userId
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null\
     */
    public function getLatestMember(int $userId)
    {
        return rep()->member->m()->where('user_id', $userId)->first();
    }

    /**
     * 获取并锁定用户会员信息，没有则创建
     *
     * @param  User  $user
     * @param  int   $cardId
     *
     * @return \App\Models\Member
     */
    public function getAndLockOrCreateMember(User $user, int $cardId)
    {
        $member = $this->getQuery()->where('user_id', $user->id)->first();
        if (!$member) {
            $memberData = ['user_id' => $user->id, 'card_id' => $cardId, 'start_at' => time()];
            $this->getQuery()->create($memberData);
        }

        return $this->getQuery()->lockForUpdate()->where('user_id', $user->id)
            ->first();
    }

    /**
     * 获取会员的过期时间
     *
     * @param int $userId
     *
     * @return int
     */
    public function getUserMemberExpiredAt($userId)
    {
        $userMember = rep()->member->getQuery()->where('user_id', $userId)
            ->where(DB::raw('start_at + duration'), '>', time())
            ->first();

        return  $userMember ? $userMember->start_at +
            $userMember->duration : time();
    }
}

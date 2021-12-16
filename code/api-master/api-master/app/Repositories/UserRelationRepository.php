<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\User;
use App\Models\UserRelation;

class UserRelationRepository extends BaseRepository
{
    public function setModel()
    {
        return UserRelation::class;
    }

    /**
     * 获取某个用户从今天开始时间起到当前时间总解锁用户数量
     *
     * @param  User  $user
     * @param  int   $startAt
     *
     * @return int
     */
    public function countUnlockUsersByTodayStartAt(User $user, int $startAt = 0)
    {
        !$startAt && $startAt = strtotime(date('Y-m-d'));

        return $this->getQuery()->where('user_id', $user->id)
            ->whereIn('type', [UserRelation::TYPE_PRIVATE_CHAT, UserRelation::TYPE_LOOK_WECHAT])
            ->where(function ($query) use ($startAt) {
                $query->where('created_at', '>', $startAt)->orWhere('updated_at',
                    '>', $startAt);
            })->count();
    }

    /**
     * 判断用户是否解锁过另一个用户
     *
     * @param  User  $consumer
     * @param  User  $beneficiary
     *
     * @return bool
     */
    public function isUnlockUser(User $consumer, User $beneficiary)
    {
        $unlockCount = $this->getQuery()->where(function ($query) use ($consumer, $beneficiary) {
            $query->where(function ($query) use ($consumer, $beneficiary) {
                $query->where('user_id', $consumer->id)->where('target_user_id', $beneficiary->id);
            })->orWhere(function ($query) use ($consumer, $beneficiary) {
                $query->where('user_id', $beneficiary->id)->where('target_user_id', $consumer->id);
            });
        })->whereIn('type', [UserRelation::TYPE_PRIVATE_CHAT, UserRelation::TYPE_LOOK_WECHAT])
            ->count();

        return $unlockCount > 0;
    }

    /**
     * 判断用户之间最近一次解锁
     *
     * @param  User  $consumer
     * @param  User  $beneficiary
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function isUnlock(User $consumer, User $beneficiary)
    {
        return $this->getQuery()->where(function ($query) use ($consumer, $beneficiary) {
            $query->where(function ($query) use ($consumer, $beneficiary) {
                $query->where('user_id', $consumer->id)->where('target_user_id', $beneficiary->id);
            })->orWhere(function ($query) use ($consumer, $beneficiary) {
                $query->where('user_id', $beneficiary->id)->where('target_user_id', $consumer->id);
            });
        })->whereIn('type', [UserRelation::TYPE_PRIVATE_CHAT, UserRelation::TYPE_LOOK_WECHAT])
            ->orderByDesc('id')
            ->first();
    }
}

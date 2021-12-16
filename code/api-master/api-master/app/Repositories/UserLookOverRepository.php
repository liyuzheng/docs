<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\User;
use App\Models\UserLookOver;
use App\Models\UserPhoto;

class UserLookOverRepository extends BaseRepository
{
    public function setModel()
    {
        return UserLookOver::class;
    }

    /**
     * 创建用户观看记录
     *
     * @param  User       $user
     * @param  UserPhoto  $userPhoto
     * @param  int        $expiredAt
     *
     * @return \App\Models\UserLookOver
     */
    public function createLookOver(User $user, UserPhoto $userPhoto, $expiredAt)
    {
        return rep()->userLookOver->m()->create([
            'user_id'     => $user->id,
            'target_id'   => $userPhoto->user_id,
            'resource_id' => $userPhoto->resource_id,
            'expired_at'  => $expiredAt
        ]);
    }
}

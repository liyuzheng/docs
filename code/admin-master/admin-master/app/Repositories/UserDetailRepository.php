<?php


namespace App\Repositories;

use App\Models\UserDetail;
use App\Foundation\Modules\Repository\BaseRepository;


class UserDetailRepository extends BaseRepository
{
    public function setModel()
    {
        return UserDetail::class;
    }

    /**
     * 根据user_id获取用户信息
     *
     * @param  int       $userId
     * @param  string[]  $fields
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getByUserId(int $userId, $fields = ['*'])
    {
        return rep()->userDetail->m()->select($fields)->where('user_id', $userId)->first();
    }

    /**
     * 根据一组用户id获取详情
     *
     * @param  array     $userIds
     * @param  string[]  $fields
     *
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getByUserIds(array $userIds, $fields = ['*'])
    {
        return rep()->userDetail->m()->select($fields)->whereIn('user_id', $userIds)->get();
    }

    /**
     * 根据邀请码获得用户ID
     *
     * @param  int  $inviteCode
     *
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|int|mixed
     */
    public function getUserIdByInviteCode(int $inviteCode)
    {
        $userDetail = rep()->userDetail->m()->select('user_id')->where('invite_code', $inviteCode)->first();

        return $userDetail ? $userDetail->user_id : 0;
    }
}

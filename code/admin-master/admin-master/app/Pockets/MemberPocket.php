<?php


namespace App\Pockets;

use App\Foundation\Modules\Pocket\BasePocket;

class MemberPocket extends BasePocket
{
    /**
     * 判断用户当前是否会员
     *
     * @param $userId
     *
     * @return bool
     */
    public function userIsMember($userId) : bool
    {
        $userMember = rep()->member->getQuery()->where('user_id', $userId)->first();

        return $userMember && $userMember->start_at + $userMember->duration > time();
    }
}

<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;

class GreetPocket extends BasePocket
{
    /**
     * 用户已经打过招呼的用户
     *
     * @param $userId
     *
     * @return mixed
     */
    public function hasGreetUserIds($userId)
    {
        return rep()->greet->m()->where('user_id', $userId)->pluck('target_id')->toArray();
    }
}

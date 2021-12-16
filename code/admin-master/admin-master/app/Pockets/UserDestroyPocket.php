<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Role;
use App\Models\UserDestroy;

class UserDestroyPocket extends BasePocket
{
    /**
     * 获得账户状态
     *
     * @param  int  $userId
     *
     * @return ResultReturn
     */
    public function getUserDestroyState(int $userId)
    {
        $latestDestroy = rep()->userDestroy->m()
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->first();
        if (!$latestDestroy || $latestDestroy->cancel_at) {
            return ResultReturn::success(['state' => 'normal']);
        }
        if ($latestDestroy->destroy_at > time() && !$latestDestroy->cancel_at) {
            return ResultReturn::success(['state' => 'destroying']);
        }
        if ($latestDestroy->destroy_at <= time()) {
            return ResultReturn::success(['state' => 'destroyed']);
        }

        return ResultReturn::success(['state' => 'normal']);
    }
}

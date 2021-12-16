<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Resource;

class UserPhotoPocket extends BasePocket
{
    /**
     * 删除用户相册
     *
     * @param  int  $userId
     *
     * @return ResultReturn
     */
    public function deleteUserAllPhotos(int $userId)
    {
        $user = rep()->user->getById($userId);
        if (!$user) {
            return ResultReturn::failed('用户不存在');
        }
        rep()->userPhoto->getQuery()->where('user_id', $userId)->delete();
        rep()->resource->getQuery()
            ->where('related_id', $userId)
            ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
            ->delete();

        return ResultReturn::success('删除用户相册成功');
    }
}

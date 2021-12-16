<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Role;
use App\Models\User;
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

    /**
     * 根据 UserDestroy 对象删除账户
     *
     * @param  UserDestroy  $destroy
     *
     * @return ResultReturn
     * 1.动态删除
     * 2.昵称改成：该用户已注销xxxx
     * 3.头像改成我提供的
     * 4.位置、签名、相册视频、微信，删掉
     * 5.不出现在首页列表里
     * 6.魅力女生身份取消
     */
    public function postDestroyByUserDestroy(UserDestroy $destroy)
    {
        $userId = $destroy->user_id;
        $state  = $this->getUserDestroyState($userId)->getData()['state'];
        $user   = rep()->user->getById($userId);
        if ($state == 'destroyed' && $user->destroy_at === 0) {
            $now = time();
            rep()->user->m()->where('id', $userId)->update(['destroy_at' => $now, 'hide' => User::HIDE]);
            pocket()->moment->deleteUserMoment($userId);
            $newNickName = trans('messages.logged_out_account_nickname_prefix', [], $user->language) . str_random(6);
            pocket()->user->updateUserNickname($userId, $newNickName);
            pocket()->user->updateUserDestroyAvatar($userId);
            pocket()->esUser->updateOrPostUserLocation($userId, 0, 0);
            rep()->userDetail->getQuery()->where('user_id', $userId)->update(['intro' => '']);
            pocket()->userPhoto->deleteUserAllPhotos($userId);
            rep()->wechat->getQuery()->where('user_id', $userId)->delete();
            $roleArr = explode(',', $user->role);
            if (in_array(Role::KEY_CHARM_GIRL, $roleArr)) {
                pocket()->userRole->removeUserRole($user, Role::KEY_CHARM_GIRL);
            }
            pocket()->esUser->updateUserFieldToEs($userId, ['destroy_at' => $now]);

            return ResultReturn::success(['user_destroy_id' => $destroy->id]);
        }

        return ResultReturn::failed(trans('messages.cancel_account_invalid_error'));
    }
}

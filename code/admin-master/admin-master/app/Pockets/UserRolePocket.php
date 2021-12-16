<?php


namespace App\Pockets;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class UserRolePocket
 * @package App\Pockets
 */
class UserRolePocket extends BasePocket
{

    /**
     * 给某个用户增加角色
     *
     * @param  User    $user
     *
     * @param  string  $role
     *
     * @return ResultReturn
     */
    public function createUserRole(User $user, string $role)
    {
        $roleId = rep()->role->m()->where('key', $role)->value('id');
        if (!$roleId) {
            return ResultReturn::failed('角色不存在，更新失败');
        }
        try {
            DB::transaction(function () use ($user, $roleId) {
                rep()->userRole->m()->create([
                    'user_id' => $user->id,
                    'role_id' => $roleId
                ]);
                pocket()->user->updateUserTableRoleField($user->id);
            });
        } catch (\Exception $exception) {
            return ResultReturn::failed('更新失败');
        }

        if ($role == User::ROLE_CHARM_GIRL) {
            mongodb('user')->where('_id', $user->id)->update([
                'charm_girl' => 1,
            ]);
            pocket()->esUser->updateUserFieldToEs($user->id, [
                'charm_girl'         => 1,
                'charm_girl_done_at' => time(),
            ]);
        }

        return ResultReturn::success('角色不存在，更新失败');
    }

    /**
     * 移除某个用户角色
     *
     * @param  User    $user
     *
     * @param  string  $role
     *
     * @return ResultReturn
     */
    public function removeUserRole(User $user, string $role)
    {
        $roleId = rep()->role->m()->where('key', $role)->value('id');
        if (!$roleId) {
            return ResultReturn::failed('角色不存在，更新失败');
        }
        try {
            DB::transaction(function () use ($user, $roleId) {
                rep()->userRole->m()->where('user_id', $user->id)->where('role_id', $roleId)->delete();
                pocket()->user->updateUserTableRoleField($user->id);
            });
        } catch (\Exception $exception) {
            return ResultReturn::failed('更新失败');
        }

        return ResultReturn::success('角色不存在，更新失败');
    }

    /**
     * @param  $users
     *
     * @return User
     */
    public function getUserRoleStr($users)
    {
        $roles = rep()->role->m()->pluck('name', 'key');
        foreach ($users as &$user) {
            $roleInfo = [];
            $roleArr  = explode(',', $user->role);
            foreach ($roleArr as $role) {
                isset($roles[$role]) && array_push($roleInfo, $roles[$role]);
            }
            $user->setAttribute('role_str', $roleInfo ? implode(',', $roleInfo) : '');
        }

        return $users;
    }
}

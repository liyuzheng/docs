<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Class UserRoleController
 * @package App\Http\Controllers
 */
class UserRoleController extends BaseController
{
    /**
     * 取消认证用户角色
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function cancelAuthUser(Request $request)
    {
        $userId = $this->getAuthUserId();
        try {
            DB::transaction(function () use ($userId) {
                $roleId = rep()->role->m()->where('key', Role::KEY_AUTH_USER)->value('id');
                rep()->userRole->m()->where('role_id', $roleId)->where('user_id', $userId)->delete();
                pocket()->user->updateUserTableRoleField($userId);
            });
        } catch (\Exception $exception) {
            return api_rr()->updateDbError(trans('messages.cancel_error'));
        }

        return api_rr()->postOK([]);
    }
}

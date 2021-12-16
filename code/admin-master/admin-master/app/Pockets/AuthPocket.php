<?php


namespace App\Pockets;


use GuzzleHttp\Client;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Option;
use App\Models\UserAuth;
use Illuminate\Support\Facades\Hash;

class AuthPocket extends BasePocket
{
    /**
     * 检查用户是否能访问此功能
     *
     * @param $userId
     * @param $route
     *
     * @return ResultReturn
     */
    public function canVisit($userId, $route)
    {
        $passRouter = [
            'admin.auth.is_need_google',
            'admin.auth.user_option',
            'admin.user.job',
            'admin.auth.user_option',
            'admin.auth.function_mapping',
            'admin.auth.add_function_mapping',
            'admin.tag.get_report_tag',
            'admin.user.all_operator',
            'admin.user.regions',
            'admin.accounts.create_customer_service_script',
            'admin.accounts.show_customer_service_script',
            'admin.accounts.update_customer_service_script',
            'admin.accounts.delete_customer_service_script'
        ];
        if (in_array($route, $passRouter)) {
            return ResultReturn::success([]);
        }
        $user     = rep()->admin->getById($userId);
        $rootRole = rep()->adminRole->m()->where('name', '超级管理员')->first();
        $userRole = $user->role_id;
        if ($userRole == $rootRole->id) {
            return ResultReturn::success([]);
        }
        $option = rep()->option->m()
            ->where('code', $route)
            ->where('type', Option::TYPE_BACK)
            ->first();
        if (!$option) {
            return ResultReturn::failed('接口尚未配置');
        }
        $exist = rep()->authority->m()
            ->where('role_id', $userRole)
            ->where('option_id', $option->id)
            ->first();
        if (!$exist) {
            return ResultReturn::failed('当前用户无法访问这个功能');
        } else {
            return ResultReturn::success([]);
        }

    }

    /**
     * 获取后台用户需不需要谷歌验证码
     *
     * @param $adminId
     * @param $path
     *
     * @return bool
     */
    public function getUserRequestTimes($adminId, $path)
    {
        $passRouter = [
            'admin.auth.is_need_google',
            'admin.auth.user_option',
            'admin.user.job',
            'admin.auth.user_option',
            'admin.auth.function_mapping',
            'admin.auth.add_function_mapping',
            'admin.tag.get_report_tag',
            'admin.user.all_operator',
            'admin.user.regions',
            'admin.accounts.create_customer_service_script',
            'admin.accounts.show_customer_service_script',
            'admin.accounts.update_customer_service_script',
            'admin.accounts.delete_customer_service_script',
        ];
        if (in_array($path, $passRouter)) {
            return false;
        }
        $option         = rep()->option->m()->where('code', $path)->first();
        $times          = rep()->adminOperationLog->m()
            ->where('path', $path)
            ->where('admin_id', $adminId)
            ->count();
        $userValidTimes = rep()->adminFunctionMapping->m()->where('option_id', $option->id)->first();
        if ($times != 0 && $times % $userValidTimes->times == 0) {
            return true;
        } else {
            return false;
        }
    }
}

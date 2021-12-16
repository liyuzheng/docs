<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\AuthRequest;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Option;

class AuthController extends BaseController
{
    public function login(AuthRequest $request)
    {
        $userName = $request->post('user_name');
        $password = $request->post('password');
        $login    = rep()->admin->m()
            ->where('type', Admin::TYPE_ADMIN)
            ->where('name', $userName)
            ->first();
        if (!$login) {
            return api_rr()->forbidCommon('用户名不存在');
        }
        if (!Hash::check($password, $login->password)) {
            return api_rr()->forbidCommon('密码错误');
        }

        return api_rr()->getOK(['admin' => $login, 'token' => pocket()->admin->getUserToken($login->id)]);
    }

    /**
     * 获取所有选项卡
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAuthOption(Request $request)
    {
        $type      = $request->get('type', 100);
        $userId    = $this->getAuthAdminId();
        $user      = rep()->admin->getById($userId);
        $userRole  = $user->role_id;
        $authority = rep()->authority->m()
            ->where('role_id', $userRole)
            ->get();
        $result    = [];
        $options   = rep()->option->m()
            ->where('p_id', 0)
            ->where('type', $type)
            ->get();
        foreach ($options as $item) {
            $result[$item->id] = [
                'id'       => $item->id,
                'p_id'     => $item->p_id,
                'name'     => $item->name,
                'code'     => $item->code,
                'children' => []
            ];
        }
        $childOptions = rep()->option->m()
            ->whereIn('p_id', $options->pluck('id')->toArray())
            ->get();
        foreach ($childOptions as $item) {
            if (key_exists($item->p_id, $result)) {
                $result[$item->p_id]['children'][] = [
                    'id'     => $item->id,
                    'p_id'   => $item->p_id,
                    'name'   => $item->name,
                    'code'   => $item->code,
                    'status' => in_array($item->id, $authority->pluck('option_id')->toArray())
                ];
            }
        }

        return api_rr()->getOK(array_values($result));
    }

    /**
     * 获取用户选项卡
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserAuth()
    {
        $result     = [];
        $userId     = $this->getAuthAdminId();
        $user       = rep()->admin->getById($userId);
        $userRole   = $user->role_id;
        $superAdmin = rep()->adminRole->m()->where('name', '超级管理员')->first();
        $superId    = $superAdmin ? $superAdmin->id : 0;
        if ($userRole == $superId) {
            $options = rep()->option->m()->where('deleted_at', 0)->get();
        } else {
            $authority = rep()->authority->m()
                ->where('role_id', $userRole)
                ->get();
            $options   = rep()->option->m()
                ->whereIn('id', $authority->pluck('option_id')->toArray())
                ->where('type', Option::TYPE_FRONT)
                ->get();
        }
        foreach ($options->where('p_id', 0) as $item) {
            $result[$item->id] = [
                'id'       => $item->id,
                'p_id'     => $item->p_id,
                'name'     => $item->name,
                'code'     => $item->code,
                'children' => []
            ];
        }
        foreach ($options->where('p_id', '!=', 0) as $item) {
            if (key_exists($item->p_id, $result)) {
                $result[$item->p_id]['children'][] = [
                    'id'   => $item->id,
                    'p_id' => $item->p_id,
                    'name' => $item->name,
                    'code' => $item->code
                ];
            }
        }

        return api_rr()->getOK(array_values($result));
    }

    /**
     * 创建&修改用户
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setUser(Request $request)
    {
        $userId   = $request->post('id');
        $name     = $request->post('name');
        $roleId   = $request->post('role_id');
        $email    = $request->post('email');
        $password = $request->post('password');
        $secret   = $request->post('secret');
        $status   = $request->post('status');
        if ($userId) {
            $user = rep()->admin->getById($userId);
            $data = [
                'role_id'  => $roleId ? $roleId : $user->role_id,
                'name'     => $name ? $name : $user->name,
                'password' => $password ? Hash::make($password) : $user->password,
                'email'    => $email ? $email : $user->email,
                'secret'   => $secret ? $secret : $user->secret,
                'status'   => ($status && $status == 'true') ? $status : $user->status,
            ];
            $user->update($data);
        } else {
            $data = [
                'type'     => Admin::TYPE_ADMIN,
                'role_id'  => $roleId,
                'name'     => $name,
                'password' => Hash::make($password),
                'email'    => $email,
                'secret'   => $secret,
                'status'   => $status,
            ];
            rep()->admin->m()->create($data);
        }

        return api_rr()->postOK([]);
    }

    /**
     * 所有角色列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function roles(Request $request)
    {
        $limit = $request->get('limit');
        $page  = $request->get('page', 1);

        $list = rep()->adminRole->m()
            ->where('status', 1)
            ->when($limit, function ($query) use ($limit, $page) {
                $query->limit($limit)->offset(($page - 1) * $limit);
            })
            ->get();

        $count = rep()->adminRole->m()
            ->where('status', 1)
            ->count();

        $options   = rep()->authority->m()
            ->whereIn('role_id', $list->pluck('id')->toArray())
            ->get();
        $optionArr = [];
        foreach ($options as $option) {
            $optionArr[$option->role_id][] = $option->option_id;
        }
        foreach ($list as $item) {
            $item->setAttribute('options', key_exists($item->id, $optionArr) ? $optionArr[$item->id] : []);
        }

        return api_rr()->getOK(['all_count' => $count, 'data' => $list]);
    }

    /**
     * 所有用户列表
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function users(Request $request)
    {
        $limit = $request->get('limit', 10);
        $page  = $request->get('page', 1);
        $list  = rep()->admin->m()
            ->select(['admin.id', 'admin.name', 'admin.email', 'admin.role_id', 'admin.secret'])
            ->join('admin_role', 'admin.role_id', '=', 'admin_role.id')
            ->with(['adminRole'])
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();
        $count = rep()->admin->m()
            ->count();

        return api_rr()->getOK(['all_count' => $count, 'data' => $list]);
    }

    /**
     * 添加选项
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setOption(Request $request)
    {
        $id   = $request->post('id');
        $type = $request->post('type');
        $pId  = $request->post('p_id');
        $name = $request->post('name');
        $code = $request->post('code');
        if ($id) {
            $option = rep()->option->getById($id);
            $data   = [
                'type' => $type ? $type : $option->type,
                'p_id' => $pId ? $pId : $option->p_id,
                'name' => $name ? $name : $option->name,
                'code' => $code ? $code : $option->code
            ];
            $option->update($data);
        } else {
            $data = [
                'type' => $type,
                'p_id' => $pId,
                'name' => $name,
                'code' => $code
            ];
            rep()->option->m()->create($data);
        }

        return api_rr()->postOK([]);
    }

    /**
     * 添加角色
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function setRole(Request $request)
    {
        $id         = $request->post('id');
        $name       = $request->post('name');
        $status     = $request->post('status');
        $options    = $request->post('options');
        $type       = $request->post('type');
        $typeOption = rep()->option->m()->where('type', $type)->get();
        $now        = time();
        if ($status) {
            $status = ($status == 1);
        }
        if ($id) {
            $adminRole = rep()->adminRole->getById($id);
            $data      = [
                'name'   => $name ? $name : $adminRole->name,
                'status' => !is_null($status) ? $status : $adminRole->status
            ];
            $adminRole->update($data);
        } else {
            $data = [
                'name'   => $name,
                'status' => 1
            ];
            $id   = rep()->adminRole->m()->create($data)->id;
        }
        $adminRole = rep()->adminRole->getById($id);
        $existAuth = rep()->authority->m()
            ->where('role_id', $adminRole->id)
            ->whereIn('option_id', $typeOption->pluck('id')->toArray())
            ->get()->pluck('option_id')->toArray();
        $retain    = array_intersect($existAuth, $options);
        rep()->authority->m()
            ->where('role_id', $adminRole->id)
            ->whereIn('option_id', $typeOption->pluck('id')->toArray())
            ->whereNotIn('option_id', $retain)
            ->delete();
        $insertArr = [];
        foreach ($options as $option) {
            if (!in_array($option, $retain)) {
                $insertArr[] = [
                    'role_id'    => $adminRole->id,
                    'option_id'  => $option,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
        }
        rep()->authority->m()->insert($insertArr);

        return api_rr()->postOK([]);
    }

    /**
     * 设置用户可访问模块
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function setUserAuth(Request $request)
    {
        $now        = time();
        $roleId     = $request->post('role_id');
        $options    = $request->post('options');
        $type       = $request->post('type');
        $typeOption = rep()->option->m()->where('type', $type)->get();
        $existAuth  = rep()->authority->m()
            ->where('role_id', $roleId)
            ->whereIn('option_id', $typeOption->pluck('id')->toArray())
            ->get()->pluck('option_id')->toArray();
        $retain     = array_intersect($existAuth, $options);
        rep()->authority->m()
            ->where('role_id', $roleId)
            ->whereIn('option_id', $typeOption->pluck('id')->toArray())
            ->whereNotIn('option_id', $retain)
            ->delete();
        $insertArr = [];
        foreach ($options as $option) {
            if (!in_array($option, $retain)) {
                $insertArr[] = [
                    'role_id'    => $roleId,
                    'option_id'  => $option,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
        }
        rep()->authority->m()->insert($insertArr);

        return api_rr()->postOK([]);
    }

    /**
     * 判断一个功能需不需要谷歌验证
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function isNeedGoogle(Request $request)
    {
        $key     = $request->get('key');
        $adminId = $this->getAuthAdminId();
        if (app()->environment() != 'production') {
            return api_rr()->getOK(['status' => false]);
        }
        $userValidTimes = rep()->adminFunctionMapping->m()->where('key', $key)->get();
        $options        = rep()->option->m()
            ->whereIn('id', $userValidTimes->pluck('option_id')->toArray())
            ->get();
        foreach ($options as $option) {
            $times          = rep()->adminOperationLog->m()
                ->where('path', $option->code)
                ->where('admin_id', $adminId)
                ->count();
            $userValidTimes = rep()->adminFunctionMapping->m()->where('option_id', $option->id)->first();
            if ($times >= $userValidTimes->times && $times % $userValidTimes->times == 0) {
                return api_rr()->getOK(['status' => true]);
            }
        }

        return api_rr()->getOK(['status' => false]);
    }

    /**
     * 功能权限映射表
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFunctionMapping(Request $request)
    {
        $limit    = $request->get('limit', 10);
        $page     = $request->get('page', 1);
        $list     = rep()->adminFunctionMapping->m()
            ->with(['option'])
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();
        $allCount = rep()->adminFunctionMapping->m()->count();

        return api_rr()->getOK(['data' => $list, 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 添加功能接口映射
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function addOrUpdateFunctionMapping(Request $request)
    {
        $id              = $request->post('id', 0);
        $key             = $request->post('key');
        $optionId        = $request->post('option_id');
        $times           = $request->post('times');
        $functionMapping = rep()->adminFunctionMapping->getById($id);
        if ($functionMapping) {
            $functionMapping->update([
                'key'       => $key ? $key : $functionMapping->key,
                'option_id' => $optionId ? $optionId : $functionMapping->option_id,
                'times'     => $times ? $times : $functionMapping->times,
            ]);
        } else {
            rep()->adminFunctionMapping->m()->create([
                'key'       => $key,
                'option_id' => $optionId,
                'times'     => $times
            ]);
        }
        return api_rr()->postOK([]);
    }
}

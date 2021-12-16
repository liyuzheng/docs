<?php


namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller;

class BaseController extends Controller
{
    /**
     * 获取认证用户id
     *
     * @return mixed
     */
    public function getAuthUserId()
    {
        return request()->user()->id;
    }

    /**
     * 认证用户uuid
     *
     * @return mixed
     */
    public function getAuthUserUUID()
    {
        return request()->user()->uuid;
    }

    /**
     * 获得认证用户
     *
     * @return mixed
     */
    public function getAuthUser()
    {
        return request()->user();
    }

    /**
     * 更具headers中是否有AuthToken获得认证用户
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getAuthUserByHeaderAuthToken()
    {
        if (!request()->headers->has('Auth-Token')) {
            return null;
        }
        $AuthToken = request()->headers->get('Auth-Token');
        $uuidArr   = explode('.', $AuthToken);
        $uuid      = $uuidArr[0];

        return rep()->user->getByUUid($uuid);
    }

    /**
     * 获得appName
     *
     * @return \App\Foundation\Modules\UserAgent\UserAgent
     */
    public function getAppName()
    {
        return user_agent()->appName;
    }

    /**
     * 从headers中获得Client-Id
     *
     * @return string|string[]|null
     */
    public function getClientId()
    {
        $clientKey = 'Client-Id';
        if (request()->headers->has($clientKey)) {
            return request()->headers->get($clientKey);
        }

        return '';
    }

    /**
     * 从headers中获得Channel
     *
     * @return string|string[]|null
     */
    public function getHeaderChannel()
    {
        $clientKey = 'Channel';
        if (request()->headers->has($clientKey)) {
            return request()->headers->get($clientKey);
        }

        return '';
    }
}

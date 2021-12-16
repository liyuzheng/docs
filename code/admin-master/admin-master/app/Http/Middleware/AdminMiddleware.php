<?php


namespace App\Http\Middleware;


use App\Models\User;
use Closure;

class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!request()->headers->has('Auth-Token')) {
            return api_rr()->authTokenMissing('请重新登录');
        }
        $now      = time();
        $token    = request()->headers->get('Auth-Token');
        $tokenArr = explode('.', $token);
        if (count($tokenArr) != 2) {
            return api_rr()->authTokenMissing('请输入正确的token');
        }
        $token   = $tokenArr[1];
        $authArr = json_decode(aes_encrypt()->decrypt($token), true);
        if (!$authArr) {
            return api_rr()->authTokenMissing('请求token不正确');
        }
        if (!key_exists('admin_id', $authArr) && !key_exists('update', $authArr) && !key_exists('delete', $authArr)) {
            return api_rr()->authTokenMissing('请求token不正确');
        }
        $adminValid = pocket()->admin->isAdminValid($authArr['admin_id']);
        if (!$adminValid) {
            return api_rr()->authTokenMissing('账号已被禁用');
        }
        $adminId    = (int)$authArr['admin_id'];
        $updateTime = $authArr['update'];
        $deleteTime = $authArr['delete'];
        $user       = new User();
        $user->setAttribute('admin_id', $adminId);
        $path = $request->route();
        $path = $path[1]['as'];
        if (app()->environment() == 'production') {
            $canVisit = pocket()->auth->canVisit($adminId, $path);
            if ($canVisit->getStatus() == false) {
                return api_rr()->forbidCommon($canVisit->getMessage());
            }
            if (pocket()->auth->getUserRequestTimes($adminId, $path)) {
                if (!request()->headers->has('google-code')) {
                    return api_rr()->forbidCommon('当前接口需要谷歌验证码校验');
                }
                $code = request()->headers->get('google-code');
                if (!pocket()->google->verifyCode($adminValid->secret, $code)) {
                    return api_rr()->forbidCommon('谷歌验证失败');
                }
            }
        }
        request()->setUserResolver(function () use ($user) {
            return $user;
        });
        $header = $next($request);
        $params = json_encode($request->all());
        $data   = ['admin_id' => $adminId, 'path' => $path, 'params' => $params, 'ip' => request()->getClientIp(), 'header' => json_encode(request()->headers->all())];
        rep()->adminOperationLog->m()->create($data);
        if ($now > $deleteTime) {
            return api_rr()->authTokenMissing('请重新登录');
        } elseif ($now > $updateTime && $now < $deleteTime) {
            $newTokenArr = [
                'admin_id' => $adminId,
                'update'   => $now + 3600,
                'delete'   => $now + 7200
            ];
            $newToken    = $adminId . '.' . aes_encrypt()->encrypt($newTokenArr);
            $header->headers->set('Auth-Token', $newToken);
        }
        $header->headers->set('Route-Name', $this->mapRoute($request->path()));

        return $header;
    }

    /**
     * 匹配路由
     *
     * @param $route
     *
     * @return mixed|string
     */
    protected function mapRoute($route)
    {
        $routesMap = config('routes_map');
        $route     = ltrim($route, '/');
        preg_match_all('/\/\d{1,}/', $route, $match);
        $replaces = $match[0] ?? [];
        foreach ($replaces as $replace) {
            $route = str_replace($replace, "/*", $route);
        }

        return $routesMap[$route] ?? "all.null";
    }
}

<?php


namespace App\Http\Middleware;

use App\Models\User;
use Closure;

class OptionalAuthenticateMiddleware
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure                   $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->headers->has('Auth-Token')) {
            $authToken = request()->headers->get('Auth-Token');
            //todo 兼容web端传入的bug,web端修复去掉这行代码
            if ($authToken == 'undefined') {
                return $next($request);
            }
            $authTokenResp = pocket()->user->verifyAuthTokenPermission($authToken);
            if (!$authTokenResp->getStatus()) {
                return api_rr()->authTokenMissing($authTokenResp->getMessage());
            }

            [$uuid, $userArr] = $authTokenResp->getData();
            $userId = (int)$userArr['id'];
            $user   = new User();
            $user->setAttribute('id', $userId)->setAttribute('uuid', $uuid);
            request()->setUserResolver(function () use ($user) {
                return $user;
            });
        }

        return $next($request);
    }
}

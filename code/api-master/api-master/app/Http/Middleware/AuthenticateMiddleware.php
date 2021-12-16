<?php


namespace App\Http\Middleware;

use App\Jobs\UpdateUserActiveAtJob;
use Closure;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Jobs\SaveNeteaseChatJob;
use App\Jobs\UpdateUserFieldToEsJob;

class AuthenticateMiddleware
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure                   $next
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->headers->has('Auth-Token')) {
            return api_rr()->authTokenMissing(trans('messages.relogin'));
        }
        $now           = time();
        $authToken     = $request->headers->get('Auth-Token');
        $authTokenResp = pocket()->user->verifyAuthTokenPermission($authToken);
        if (!$authTokenResp->getStatus()) {
            return api_rr()->authTokenMissing($authTokenResp->getMessage());
        }

        [$uuid, $userArr] = $authTokenResp->getData();
        $userId    = (int)$userArr['id'];
        $clientKey = 'Client-Id';
        $clientId  = $request->headers->has($clientKey) ? $request->headers->get($clientKey) : '';
        $blackResp = pocket()->blacklist->verifyIsBlackByUserIdOrClientId($userId, $clientId);
        if (!$blackResp->getStatus()) {
            return api_rr()->userBlacklist($blackResp->getMessage());
        }

        $user = new User();
        $user->setAttribute('id', $userId)->setAttribute('uuid', $uuid);
        request()->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $next($request);
        if ($now > $userArr['update'] && $now < $userArr['delete']) {
            $newTokenArr = ['id' => $userId, 'update' => $now + 86400 * 7 - 3600, 'delete' => $now + 86400 * 7];
            $newToken    = $uuid . '.' . aes_encrypt()->encrypt($newTokenArr);
            $response->headers->set('Auth-Token', $newToken);
        }

        if ($isUpdateActiveAt = pocket()->user->whetherUpdateUserActiveAt($userId, $now)) {
            $updateUserActiveAt = (new UpdateUserActiveAtJob($userId, $now, user_agent()->os,
                user_agent()->clientVersion, $request->header('request-cu-language')))
                ->onQueue('update_user_active_at');
            dispatch($updateUserActiveAt);

            $updateUserField = (new UpdateUserFieldToEsJob($userId, ['active_at' => $now]))
                ->onQueue('update_user_field_to_es');
            dispatch($updateUserField);
        }

        if (pocket()->user->whetherUpdateUserRemainLog($user->id)) {
            pocket()->user->postStatRemainLoginLog($user->id);
        }

        return $response;
    }
}

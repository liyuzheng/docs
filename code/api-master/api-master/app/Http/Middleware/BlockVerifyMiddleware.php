<?php


namespace App\Http\Middleware;

use App\Models\Blacklist;
use Closure;

class BlockVerifyMiddleware
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure                   $next
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return api_rr()->authTokenMissing(trans('messages.need_login'));
        }

        $uuid = $request->route('uuid');
        if ($uuid) {
            $toUser = rep()->user->getQuery()->where('uuid', (int)$uuid)->first();

            if ($toUser) {
                $blocked = rep()->blacklist->getQuery()
                    ->where(function ($query) use ($toUser, $user) {
                        $query->where('user_id', $toUser->id)->where('related_id', $user->id)
                            ->where('related_type', Blacklist::RELATED_TYPE_MANUAL);
                    })->orWhere(function ($query) use ($toUser, $user) {
                        $query->where('user_id', $user->id)->where('related_id', $toUser->id)
                            ->where('related_type', Blacklist::RELATED_TYPE_MANUAL);
                    })->first();

                if ($blocked) {
                    if ($blocked->user_id == $user->id) {
                        return api_rr()->blockedByUser(trans('messages.block_target_view_notice'));
                    } elseif ($blocked->related_id == $user->id) {
                        return api_rr()->blockedByUser();
                    }
                }
            }
        }

        return $next($request);
    }
}

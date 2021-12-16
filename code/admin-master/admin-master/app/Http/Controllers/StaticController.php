<?php


namespace App\Http\Controllers;


use Illuminate\Http\Request;

class StaticController extends BaseController
{
    /**
     * 反垃圾统计
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function msgStatic(Request $request)
    {
        $limit        = (int)request('limit', 10);
        $defaultCount = (int)request('count', 10);
        $sort         = request('sort', "has_wechat");
        $page         = request('page', 1);
        $mongoRes     = mongodb('msg_static')
            ->when($sort === 'has_wechat', function ($q) use ($defaultCount) {
                $q->where('has_wechat', '>=', $defaultCount)->orderBy('has_wechat', 'DESC');
            }, function ($q) use ($defaultCount) {
                $q->where('active', '>=', $defaultCount)->orderBy('active', 'DESC');
            })
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();
        $result       = [];
        $allCount     = mongodb('msg_static')->where('has_wechat', '>=', 10)->count();
        $userIds      = $mongoRes->pluck('_id')->toArray();
        $users        = rep()->user->m()->whereIn('id', $userIds)->get();
        $append       = [
            'member',
            'auth_user',
            'charm_girl',
            'user_detail',
        ];
        pocket()->user->appendToUsers($users, $append);
        $redisKey     = config('redis_keys.blacklist.user.key');
        $blockArr     = redis()->client()->zRange($redisKey, 0, -1, ['withscores' => true]);
        $blackUserIds = [];
        $currentNow   = time();
        foreach ($blockArr as $blackId => $timestamp) {
            if (!$timestamp || $timestamp > $currentNow) {
                $blackUserIds[] = $blackId;
            }
        }

        foreach ($mongoRes as $mongo) {
            $user = $users->where('id', $mongo['_id'])->first();
            if (in_array($mongo['_id'], $blackUserIds, true)) {
                continue;
            }
            $user && $result[] = [
                'uuid'     => $user->uuid,
                'member'   => $user->member,
                'nickname' => $user->nickname,
                'count'    => $mongo['has_wechat'] ?? 0,
                'msg'      => $mongo['msg'] ?? "",
                'intro'    => optional($user->user_detail)->intro,
            ];
        }

        return api_rr()->getOK(['data' => $result, 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 女生给别人发的第一条异常记录
     *
     * @param  Request  $request
     */
    public function showMsg(Request $request)
    {
        $uuid   = request('uuid');
        $user   = rep()->user->m()->where('uuid', $uuid)->first();
        $result = [];
        $user && $result = mongodb('msg_static_detail')->where('user_id', $user->id)->get();

        return api_rr()->getOK(['data' => $result]);
    }
}

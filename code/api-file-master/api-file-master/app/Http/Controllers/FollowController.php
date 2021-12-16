<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\User\UserFollowRequest;

/**
 * Class FollowController
 * @package App\Http\Controllers
 */
class FollowController extends BaseController
{
    /**
     * 用户批量关注
     *
     * @param  UserFollowRequest  $request
     *
     * @return JsonResponse
     */
    public function batchFollow(UserFollowRequest $request)
    {
        $user      = rep()->user->getQuery()->find($request->user()->id);
        $reviewing = pocket()->account->checkUserReviewing($user);
        if ($reviewing->getStatus() == false) {
            return api_rr()->forbidCommon(trans('messages.review_not_follow'));
        }
        $tuuids       = $request->post('uuids');
        $lockRedisKey = sprintf(config('redis_keys.follow_lock'), $user->id);
        $redisKey     = sprintf(config('redis_keys.is_follow.key'), $user->id);
        $client       = redis()->client();
        if ($client->get($lockRedisKey)) {
            return api_rr()->forbidCommon('操作太快啦~');
        } else {
            $client->set($lockRedisKey, true);
            $client->expire($lockRedisKey, 3);
        }
        $count = count($client->zRange($redisKey, 0, -1));
        if ($count >= 50) {
            return api_rr()->forbidCommon(trans('messages.today_follow_limit'));
        }
        foreach ($tuuids as $tuuid) {
            $client->zAdd($redisKey, time(), $tuuid);
        }
        if ($count == 0) {
            $endTime = strtotime(date('Y-m-d')) + 86400;
            redis()->client()->expire($redisKey, $endTime - time());
        }
        $targetUsers = rep()->user->getQuery()->select('id', 'uuid', 'language')
            ->whereIn('uuid', $tuuids)->get();
        $result      = pocket()->userFollow->batchFollow($user, $targetUsers);

        if ($result->getStatus() == false) {
            return api_rr()->notFoundResult($result->getMessage());
        }

        return api_rr()->postOK([]);
    }

    /**
     * 用户批量取消关注
     *
     * @param  UserFollowRequest  $request
     *
     * @return JsonResponse
     */
    public function batchUnFollow(UserFollowRequest $request)
    {
        $userId   = $this->getAuthUserId();
        $tuuids   = $request->post('uuids');
        $tUserIds = rep()->user->getIdsByUUids($tuuids);
        $result   = pocket()->userFollow->batchUnFollow($userId, $tUserIds);

        if ($result->getStatus() == false) {
            return api_rr()->notFoundResult($result->getMessage());
        }

        return api_rr()->deleteOK([]);
    }

    /**
     * 取消关注单个用户
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     */
    public function unFollow(Request $request, int $uuid)
    {
        $userId    = $this->getAuthUserId();
        $user      = rep()->user->getQuery()->find($userId);
        $reviewing = pocket()->account->checkUserReviewing($user);
        if ($reviewing->getStatus() == false) {
            return api_rr()->forbidCommon(trans('messages.review_not_unfollow'));
        }
        $tUserIds = rep()->user->getIdsByUUids([$uuid]);
        $result   = pocket()->userFollow->batchUnFollow($userId, $tUserIds);

        if ($result->getStatus() == false) {
            return api_rr()->notFoundResult($result->getMessage());
        }

        return api_rr()->deleteOK([]);
    }
}

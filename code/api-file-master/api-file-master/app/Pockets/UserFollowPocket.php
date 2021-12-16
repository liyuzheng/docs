<?php


namespace App\Pockets;


use App\Constant\NeteaseCustomCode;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UserFollowPocket extends BasePocket
{
    /**
     * 批量关注
     *
     * @param  User        $user
     * @param  Collection  $targetUsers
     *
     * @return ResultReturn
     */
    public function batchFollow(User $user, Collection $targetUsers)
    {
        $tUserIds           = $targetUsers->pluck('id')->toArray();
        $existFollowedUsers = rep()->userFollow->m()->where('user_id', $user->id)
            ->whereIn('follow_id', $tUserIds)
            ->withTrashed()->get()->pluck('follow_id')->toArray();

        $followResp = DB::transaction(function () use ($user, $tUserIds) {
            $followedUserIds = rep()->userFollow->getQuery()->where('user_id', $user->id)
                ->whereIn('follow_id', $tUserIds)->pluck('follow_id')->toArray();
            $notFollowUsers  = array_diff($tUserIds, $followedUserIds);
            if (count($notFollowUsers) == 0) {
                return ResultReturn::failed(trans('messages.repeat_follow_error'));
            }

            $timestamps = ['created_at' => time(), 'updated_at' => time()];
            $createData = [];
            foreach ($notFollowUsers as $notFollowUser) {
                $createData[] = array_merge($timestamps,
                    ['user_id' => $user->id, 'follow_id' => $notFollowUser]);
            }

            rep()->userFollow->m()->insert($createData);
            rep()->userDetail->m()->where('user_id', $user->id)
                ->increment('follow_count', count($createData));
            rep()->userDetail->m()->whereIn('user_id', $notFollowUsers)
                ->increment('followed_count');

            return ResultReturn::success($notFollowUsers);
        });

        if ($followResp->getStatus()) {
            $notFollowUsers = $targetUsers->whereIn('id', $followResp->getData());
            $follows        = rep()->userDetail->m()->whereIn('user_id', $notFollowUsers)->get();
            foreach ($notFollowUsers as $notFollowUser) {
                $followedCount = $follows->where('user_id', $notFollowUser->id)->first();
                $followedCount && pocket()->esUser->updateUserFieldToEs($notFollowUser->id, [
                    'followed_count' => $followedCount->followed_count + 1,
                ]);
            }
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->userFollow,
                'sendFollowMessage',
                [$user, $notFollowUsers, $existFollowedUsers]
            );
        }

        return $followResp;
    }

    /**
     * 发送被关注的系统消息
     *
     * @param  \App\Models\User                          $followUser
     * @param  \Illuminate\Database\Eloquent\Collection  $beFollowUsers
     * @param  array                                     $existFollowedUsers
     */
    public function sendFollowMessage($followUser, $beFollowUsers, array $existFollowedUsers)
    {
        $body            = ['type' => NeteaseCustomCode::BE_FOLLOW, 'data' => ['message' => '']];
        $extension       = ['option' => ['push' => false, 'badge' => false]];
        $sender          = config('custom.little_helper_uuid');

        $beFollowUsersId   = $beFollowUsers->pluck('id')->toArray();
        $beFollowUsersInfo = rep()->userDetail->getByUserIds($beFollowUsersId);
        foreach ($beFollowUsers as $beFollowUser) {
            $beFollowUserInfo = $beFollowUsersInfo->where('user_id', $beFollowUser->id)->first();
            if (!in_array($beFollowUser->id, $existFollowedUsers)) {
                $messageTemplate = trans('messages.follow_notice_tmpl', [], $beFollowUser->language);
                $runVersion = $beFollowUserInfo->run_version;
                if (version_compare($runVersion, '2.1.0', '>=')) {
                    $body['data']['messages'] = sprintf($messageTemplate, $followUser->nickname);
                    pocket()->common->commonQueueMoreByPocketJob(pocket()->netease, 'msgSendCustomMsg',
                        [$sender, $beFollowUser->uuid, $body, $extension]);
                } else {
                    $message = sprintf($messageTemplate, $followUser->nickname);
                    pocket()->common->commonQueueMoreByPocketJob(pocket()->netease, 'msgSendMsg',
                        [$sender, $beFollowUser->uuid, $message]);
                }
            }
        }
    }

    /**
     * 批量取消关注
     *
     * @param  int    $userId
     * @param  array  $tUserIds
     *
     * @return ResultReturn
     */
    public function batchUnFollow(int $userId, array $tUserIds)
    {
        $unFollowResp = DB::transaction(function () use ($userId, $tUserIds) {
            $followedUserIds = rep()->userFollow->getQuery()->where('user_id', $userId)
                ->whereIn('follow_id', $tUserIds)->pluck('follow_id')->toArray();
            if (count($followedUserIds) == 0) {
                return ResultReturn::failed(trans('messages.not_followers'));
            }

            rep()->userFollow->m()->where('user_id', $userId)
                ->whereIn('follow_id', $followedUserIds)->delete();
            rep()->userDetail->m()->where('user_id', $userId)
                ->decrement('follow_count', count($followedUserIds));

            rep()->userDetail->m()->whereIn('user_id', $followedUserIds)
                ->decrement('followed_count');

            return ResultReturn::success($followedUserIds);
        });

        if ($unFollowResp->getStatus()) {
            $followedUserIds = $unFollowResp->getData();

            $follows = rep()->userDetail->m()->whereIn('user_id', $followedUserIds)->get();
            foreach ($followedUserIds as $followedUserId) {
                $followedCount = $follows->where('user_id', $followedUserId)->first();
                $followedCount && pocket()->esUser->updateUserFieldToEs($followedUserId, [
                    'followed_count' => $followedCount->followed_count - 1,
                ]);
            }
        }

        return $unFollowResp;
    }
}

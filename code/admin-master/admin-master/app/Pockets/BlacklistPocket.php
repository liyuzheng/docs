<?php


namespace App\Pockets;

use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\Blacklist;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;

class BlacklistPocket extends BasePocket
{
    /**
     * 拉黑
     *
     * @param  int     $userId
     * @param  string  $reason
     * @param  string  $remark
     * @param  int     $expiredAt
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function postGlobalBlockUser(int $userId, string $reason, string $remark, int $expiredAt) : ResultReturn
    {
        $user                = rep()->user->getById($userId);
        $userDetail          = rep()->userDetail->getByUserId($userId);
        $blacklistUserData   = [
            'related_type' => Blacklist::RELATED_TYPE_OVERALL,
            'related_id'   => $user->id,
            'user_id'      => 0,
            'reason'       => $reason,
            'remark'       => $remark,
            'expired_at'   => $expiredAt
        ];
        $blacklistClientData = [
            'related_type' => Blacklist::RELATED_TYPE_CLIENT,
            'related_id'   => $userDetail->client_id,
            'user_id'      => $user->id,
            'reason'       => $reason,
            'remark'       => $remark,
            'expired_at'   => $expiredAt
        ];
        $blacklistOverAll    = rep()->blacklist->m()->create($blacklistClientData);
        $blacklistClient     = rep()->blacklist->m()->create($blacklistUserData);
        $redisKey            = config('redis_keys.blacklist.client.key');
        $redisUserKey        = config('redis_keys.blacklist.user.key');
        redis()->client()->zAdd($redisKey, $expiredAt, $userDetail->client_id);
        redis()->client()->zAdd($redisUserKey, $expiredAt, $user->id);
        pocket()->netease->userBlock($user->uuid);

        return ResultReturn::success([
            'ids' => [$blacklistOverAll->id, $blacklistClient->id]
        ]);
    }

    /**
     * 获取黑名单列表
     *
     * @param  User  $user
     *
     * @return mixed
     */
    public function getBlackList(User $user)
    {
        $black      = collect(Blacklist::RELATED_TYPE_ARR);
        $blacklists = rep()->blacklist->m()
            ->where(function ($q) {
                $q->where('expired_at', '>=', time())->orWhere('expired_at', 0);
            })
            ->whereIn('related_type', [
                //                Blacklist::RELATED_TYPE_MANUAL,
                Blacklist::RELATED_TYPE_OVERALL,
                Blacklist::RELATED_TYPE_CLIENT,
                Blacklist::RELATED_TYPE_FACE,
            ])
            ->where('user_id', $user->id)
            ->where('deleted_at', 0)
            ->get();
        $blackInfo  = $blacklists->where('user_id', $user->id)
            ->pluck('related_type')
            ->map(function ($relatedType) use ($black) {
                return $black[$relatedType] ?? 0;
            })->implode(',');

        return $blackInfo;
    }

    /**
     * 清除一个用户的账号和设备拉黑
     *
     * @param $userId
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function delBlackList($userId)
    {
        $black = rep()->blacklist->m()->where('related_type', Blacklist::RELATED_TYPE_OVERALL)->where('related_id', $userId)->first();
        if ($black) {
            $black->delete();
            $redisKey = config('redis_keys.blacklist.user.key');
            redis()->client()->zRem($redisKey, $black->related_id);
            $user = rep()->user->getById($black->related_id);
            pocket()->netease->userUnblock($user->uuid);
        }
        $black = rep()->blacklist->m()->where('related_type', Blacklist::RELATED_TYPE_CLIENT)->where('user_id', $userId)->first();
        if ($black) {
            $black->delete();
            $redisKey = config('redis_keys.blacklist.client.key');
            redis()->client()->zRem($redisKey, $black->related_id);
        }

        return ResultReturn::success([]);
    }
}

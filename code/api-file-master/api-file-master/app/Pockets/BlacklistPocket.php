<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\Blacklist;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\SwitchModel;
use App\Models\UserSwitch;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Collection;
use App\Models\User;

class BlacklistPocket extends BasePocket
{
    /**
     * 批量拉黑&取消拉黑
     *
     * @param  string  $type
     * @param  int     $userId
     * @param  array   $tUserids
     *
     * @return ResultReturn
     */
    public function batchAddOrDelete(string $type, int $userId, array $tUserids)
    {
        $blackIds = rep()->blacklist->m()
            ->where('user_id', $userId)
            ->where('related_type', Blacklist::RELATED_TYPE_MANUAL)
            ->whereIn('related_id', $tUserids)
            ->get()
            ->pluck('related_id')
            ->toArray();
        switch ($type) {
            case 'add':
                $blacks = array_diff($tUserids, $blackIds);
                if (count($blacks) == 0) {
                    return ResultReturn::failed(trans('messages.user_has_blocked'));
                }

                $createData = [];
                foreach ($blacks as $black) {
                    $createData[] = [
                        'related_type' => Blacklist::RELATED_TYPE_MANUAL,
                        'user_id'      => $userId,
                        'related_id'   => $black,
                        'reason'       => 'manual',
                        'expired_at'   => 0,
                        'created_at'   => time(),
                        'updated_at'   => time()
                    ];
                    pocket()->blacklist->cacheUserManualBlacklist($userId, $black);
                }

                rep()->blacklist->m()->insert($createData);
                break;
            case 'delete':
                if (count($blackIds) == 0) {
                    return ResultReturn::failed(trans('messages.not_followers'));
                }

                rep()->blacklist->m()
                    ->where('user_id', $userId)
                    ->where('related_type', Blacklist::RELATED_TYPE_MANUAL)
                    ->whereIn('related_id', $blackIds)
                    ->delete();
                foreach ($blackIds as $black) {
                    pocket()->blacklist->cancelCacheUserManualBlacklist($userId, $black);
                }
                break;
            default:
                return ResultReturn::failed(trans('messages.illegal_params_error'));
        }

        return ResultReturn::success($blackIds);
    }

    /**
     * 验证用户是否被拉黑
     *
     * @param  int     $userId
     * @param  string  $clientId
     *
     * @return ResultReturn
     */
    public function verifyIsBlackByUserIdOrClientId($userId, $clientId)
    {
        $verifyByUserIdResp = $this->verifyIsBlackByUserId($userId);
        if (!$verifyByUserIdResp->getStatus()) {
            return $verifyByUserIdResp;
        }

        $verifyByClientIdResp = $this->verifyIsBlackByClientId($clientId);
        if (!$verifyByClientIdResp->getStatus()) {
            return $verifyByClientIdResp;
        }

        return ResultReturn::success(null);
    }

    /**
     * 通过 client id 判断是否被拉黑
     *
     * @param  string  $clientId
     *
     * @return ResultReturn
     */
    public function verifyIsBlackByClientId($clientId)
    {
        $redisKey    = config('redis_keys.blacklist.client.key');
        $blackClient = redis()->client()->zscore($redisKey, $clientId);
        if ($blackClient === false) {
            return ResultReturn::success(null);
        }

        if ($clientId && ($blackClient == 0 || $blackClient - time() > 0)) {
            $blackInfo = rep()->blacklist->getBlacklistInfo(null, $clientId);
            $template  = trans('messages.has_blocked_time_reason_tmpl');
            $message   = sprintf($template, !$blackInfo->expired_at ? '永久' :
                date('Y-m-d H:i:s', $blackInfo->getRawOriginal('expired_at'))
                , $blackInfo->reason);

            return ResultReturn::failed($message);
        }

        return ResultReturn::success(null);
    }

    /**
     * 根据用户id 判断是否被拉黑
     *
     * @param  int  $userId
     *
     * @return ResultReturn
     */
    public function verifyIsBlackByUserId($userId)
    {
        $redisUserKey = config('redis_keys.blacklist.user.key');
        $black        = redis()->client()->zscore($redisUserKey, $userId);
        if ($black === false) {
            return ResultReturn::success(null);
        }

        if ($userId && ($black == 0 || $black - time() > 0)) {
            $blackInfo = rep()->blacklist->getBlacklistInfo($userId, null);
            $template  = trans('messages.has_blocked_time_reason_tmpl');
            $message   = sprintf($template, !$blackInfo->expired_at ? '永久' :
                date('Y-m-d H:i:s', $blackInfo->getRawOriginal('expired_at'))
                , $blackInfo->reason);

            return ResultReturn::failed($message);
        }


        return ResultReturn::success(null);
    }

    /**
     * 某个用户拉黑的其他用户ids
     *
     * @param $userId
     *
     * @return array
     */
    public function userBlackIds($userId)
    {
        /** 用户手动拉黑的  */
        $manualBlacklist = pocket()->blacklist->getCacheUserManualBlacklist($userId);
        /** 开启通讯录拉黑后，通讯录黑名单列表  */
        $blockArr  = [];
        $blackList = [];
        if (rep()->userSwitch->getPhoneShieldStatus($userId)) {
            $blackList = pocket()->blacklist->getCacheUserPhoneBlacklist($userId);
            $redisKey  = config('redis_keys.blacklist.user.key');
            $blockArr  = redis('read_only')->client()->zRange($redisKey, 0, -1, ['withscores' => true]);
        }
        $blackUserIds = [];
        foreach ($blockArr as $blackId => $timestamp) {
            if (!$timestamp || $timestamp > time()) {
                $blackUserIds[] = $blackId;
            }
        }
        $excUserIds = array_merge($blackUserIds, $blackList, $manualBlacklist,
            config('custom.allow_sandbox_users'));
        $excUserIds = arr_str_to_int($excUserIds);

        return $excUserIds;
    }

    /**
     * 拉黑
     *
     * @param  int     $userId     用户ID
     * @param  string  $reason     拉黑理由
     * @param  string  $remark     标记
     * @param  int     $expiredAt  过期时间
     */
    public function postGlobalBlockUser(int $userId, string $reason, string $remark, int $expiredAt)
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
     * 获得跳过的用户ID
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function getBreakBlockUserId()
    {
        $cacheKey = config('redis_keys.cache.auto_block_break_users');
        if (!Redis::exists($cacheKey)) {
            $lock = new RedisLock(Redis::connection(), 'lock:' . $cacheKey, 3);
            $lock->block(3, function () use ($cacheKey) {
                $values   = rep()->config->getQuery()
                    ->whereIn('key', ['system_helper', 'netease_kf'])
                    ->pluck('value')
                    ->toArray();
                $values[] = config('custom.recharge_helper_uuid');
                array_merge($values, pocket()->util->getIosAuditUserListUUIds());
                $users = rep()->user->getByUUids($values);
                Redis::set($cacheKey, json_encode($users->pluck('id')->toArray()));
            });
        }

        return json_decode(Redis::get($cacheKey), true);
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
     * 获取用户黑名单id
     *
     * @param $userId
     *
     * @return array
     */
    public function feedBlackUserIds($userId)
    {
        $switchKey = rep()->switchModel->m()
            ->where('key', SwitchModel::KEY_LOCK_PHONE)
            ->first();
        $switch    = rep()->userSwitch->m()
            ->where('user_id', $userId)
            ->where('switch_id', $switchKey->id)
            ->where('status', UserSwitch::STATUS_OPEN)
            ->first();
        /** 黑名单  */
        $blackList = rep()->blacklist->m()
            ->where('user_id', $userId)
            ->when(!$switch, function ($query) {
                $query->where('related_type', Blacklist::RELATED_TYPE_MANUAL);
            })->where(function ($query) {
                $query->where('expired_at', 0)->orWhere('expired_at', '>', time());
            })->where('deleted_at', 0)
            ->pluck('related_id')
            ->toArray();

        $redisKey     = config('redis_keys.blacklist.user.key');
        $blockArr     = redis()->client()->zRange($redisKey, 0, -1, ['withscores' => true]);
        $blackUserIds = [];
        $currentNow   = time();
        foreach ($blockArr as $blackId => $timestamp) {
            if (!$timestamp || $timestamp > $currentNow) {
                $blackUserIds[] = $blackId;
            }
        }

        $excUserIds = array_merge($blackUserIds, $blackList, config('custom.allow_sandbox_users'));
        foreach ($excUserIds as $k => $excUserId) {
            $excUserIds[$k] = (int)$excUserId;
        }

        return $excUserIds;
    }

    /**
     * 获取某个用户手动拉黑的黑名单
     *
     * @param       $userId
     * @param  int  $key
     *
     * @return array
     */
    public function getCacheUserManualBlacklist($userId, $key = Blacklist::RELATED_TYPE_MANUAL) : array
    {
        $client           = redis()->client();
        $redisKey         = sprintf(config('redis_keys.user_blacklist_manual.key'), $userId);
        $cacheManualBlack = $client->sMembers($redisKey);
        if ($cacheManualBlack) {
            return $cacheManualBlack;
        }

        $blackList = rep()->blacklist->m()
            ->where('user_id', $userId)
            ->where('related_type', $key)
            ->pluck('related_id')
            ->toArray();

        $client->sAdd($redisKey, ...$blackList);
        $client->expire($redisKey, 24 * 60 * 60);

        return $blackList;
    }

    /**
     * 缓存某一个用户手动拉黑某一个人
     *
     * @param       $userId
     * @param       $targetUserId
     * @param  int  $key
     *
     * @return ResultReturn
     */
    public function cacheUserManualBlacklist($userId, $targetUserId) : ResultReturn
    {
        $client   = redis()->client();
        $redisKey = sprintf(config('redis_keys.user_blacklist_manual.key'), $userId);

        $client->sAdd($redisKey, $targetUserId);
        $client->expire($redisKey, 24 * 60 * 60);

        return ResultReturn::success([]);
    }

    /**
     * 取消缓存某一个用户手动拉黑某一个人
     *
     * @param $userId
     * @param $targetUserId
     *
     * @return ResultReturn
     */
    public function cancelCacheUserManualBlacklist($userId, $targetUserId) : ResultReturn
    {
        $client   = redis()->client();
        $redisKey = sprintf(config('redis_keys.user_blacklist_manual.key'), $userId);

        $client->sRem($redisKey, $targetUserId);

        return ResultReturn::success([]);
    }

    /**
     * 获取某个用户通讯录拉黑的列表
     *
     * @param       $userId
     * @param  int  $key
     *
     * @return array
     */
    public function getCacheUserPhoneBlacklist($userId, $key = Blacklist::RELATED_TYPE_MOBILE) : array
    {
        $client          = redis()->client();
        $redisKey        = sprintf(config('redis_keys.user_blacklist_phone.key'), $userId);
        $cachePhoneBlack = $client->sMembers($redisKey);
        if ($cachePhoneBlack) {
            return $cachePhoneBlack;
        }

        $blackList = rep()->blacklist->m()
            ->where('user_id', $userId)
            ->where('related_type', $key)
            ->where('related_id', '!=', $userId)
            ->pluck('related_id')
            ->toArray();

        $client->sAdd($redisKey, ...$blackList);
        $client->expire($redisKey, 24 * 60 * 60);

        return $blackList;
    }

    /**
     * 清楚一个用户的账号和设备拉黑
     *
     * @param $userId
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
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

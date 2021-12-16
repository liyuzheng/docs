<?php


namespace App\Pockets;


use App\Foundation\Handlers\Tools;
use App\Foundation\Modules\Pocket\BasePocket;
use GuzzleHttp\Exception\GuzzleException;

class ColdStartUserPocket extends BasePocket
{
    /**
     * 更新冷起用户活跃时间
     *
     * @param $user
     * @param $os
     * @param $runVersion
     *
     * @throws GuzzleException
     */
    public function updateColdStartUserActiveAt($user, $os, $runVersion)
    {
        $api = sprintf(config('custom.internal.update_users_active_url'), $user->uuid);
        Tools::getHttpRequestClient()->post($api,
            ['json' => ['os' => $os, 'run_version' => $runVersion], 'headers' => ['Host' => 'api.okacea.com']]);
    }

    /**
     * 更新冷起用户位置
     *
     * @param $user
     * @param $lng
     * @param $lat
     *
     * @throws GuzzleException
     */
    public function updateColdStartUserLocation($user, $lng, $lat)
    {
        $api = sprintf(config('custom.internal.update_users_location_url'), $user->uuid);
        Tools::getHttpRequestClient()->post($api,
            ['json' => ['lng' => $lng, 'lat' => $lat], 'headers' => ['Host' => 'api.okacea.com']]);
    }

    /**
     * 更新冷起用户开关
     *
     * @param         $user
     * @param  array  $switches
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     */
    public function updateColdStartUserSwitches($user, array $switches)
    {
        $api = sprintf(config('custom.internal.update_users_switches'), $user->uuid);
        return Tools::getHttpRequestClient()->post($api,
            ['json' => $switches, 'headers' => ['Host' => 'api.okacea.com']]);
    }

    /**
     * @param $user
     * @param $weChat
     *
     * @throws GuzzleException
     */
    public function updateColdStartUserWeChat($user, $weChat)
    {
        $api = sprintf(config('custom.internal.update_users_wechat'), $user->uuid);
        Tools::getHttpRequestClient()->post($api,
            [
                'json'    => [
                    'wechat'  => $weChat->getRawOriginal('wechat'),
                    'qr_code' => $weChat->getRawOriginal('qr_code')
                ],
                'headers' => ['Host' => 'api.okacea.com']
            ]);
    }

    /**
     * 判断搜是否冷起同步用户
     *
     * @param $userId
     *
     * @return int
     */
    public function isColdStartUser($userId)
    {
        $cacheKey = sprintf(config('redis_keys.cache.cold_start_user_cache'), $userId);
        if (!redis()->client()->exists($cacheKey)) {
            $userIds     = mongodb('mark_charm_girl')->where('user_id', $userId)
                ->pluck('user_id')->toArray();
            $isColdStart = (int)(!empty($userIds));
            redis()->client()->set($cacheKey, $isColdStart);
            redis()->client()->expire($cacheKey, 3600);
        } else {
            $isColdStart = (int)redis()->client()->get($cacheKey);
        }

        return $isColdStart;
    }
}

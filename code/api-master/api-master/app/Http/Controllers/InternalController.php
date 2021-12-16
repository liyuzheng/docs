<?php


namespace App\Http\Controllers;


use App\Jobs\ColdStartMessageCcJob;
use App\Jobs\UpdateUserActiveAtJob;
use App\Jobs\UpdateUserFieldToEsJob;
use App\Jobs\UpdateUserLocationToEsJob;
use Illuminate\Http\Request;
class InternalController extends BaseController
{
    /**
     * 解二维码内容
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function parseQrCode(Request $request)
    {
        ini_set('memory_limit', '1024M');
        $path = request()->get('path');
        $resp = pocket()->tools->getParsingQrCode(public_path($path));
        if ($resp->getStatus()) {
            return api_rr()->postOK(['content' => $resp->getData()]);
        }

        return api_rr()->postOK(['content' => '']);
    }

    /**
     * 下载冷起项目的资源
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function downloadsColdStartResources(Request $request)
    {
        $paths     = $request->paths;
        $cdnDomain = config('custom.cold_start_cdn_domain');
        foreach ($paths as $path) {
            storage()->put($path, file_get_contents($cdnDomain . '/' . $path));
        }

        return api_rr()->postOK([]);
    }

    /**
     * 冷起项目云信消息抄送
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function coldStartMessageCc(Request $request)
    {
        $callbackData = $request->all();
        dispatch(new ColdStartMessageCcJob($callbackData))
            ->onQueue('cold_start_message_cc');

        return api_rr()->postOK([]);
    }

    /**
     * 更新冷起应用用户的定位
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function updateColdStartUserLocation(Request $request, $uuid)
    {
        $lng  = $request->lng;
        $lat  = $request->lat;
        $user = rep()->user->getQuery()->where('uuid', $uuid)->first();
        if ($user) {
            pocket()->common->commonQueueMoreByPocketJob(pocket()->account,
                'updateLocation', [$user->id, $lng, $lat]);
            $updateEsJob = (new UpdateUserLocationToEsJob($user->id, $lng, $lat))
                ->onQueue('update_user_location_to_es');
            dispatch($updateEsJob);

            $cityName = pocket()->userDetail->getCityByLoc($lng, $lat);
            if ($cityName) {
                rep()->userDetail->m()->where('user_id', $user->id)
                    ->update(['region' => $cityName]);
            }

        }

        return api_rr()->postOK([]);
    }

    /**
     * 更新冷起应用用户的活跃时间
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateColdStartUserActiveTime(Request $request, $uuid)
    {
        $user = rep()->user->getQuery()->where('uuid', $uuid)->first();

        if ($user) {
            $now        = time();
            $os         = $request->os;
            $runVersion = $request->run_version;
            if ($isUpdateActiveAt = pocket()->user->whetherUpdateUserActiveAt($user->id, $now)) {
                $updateUserActiveAt = (new UpdateUserActiveAtJob($user->id, $now,
                    $os, $runVersion, 'zh'))
                    ->onQueue('update_user_active_at');
                dispatch($updateUserActiveAt);

                $updateUserField = (new UpdateUserFieldToEsJob($user->id, ['active_at' => $now]))
                    ->onQueue('update_user_field_to_es');
                dispatch($updateUserField);
            }
        }

        return api_rr()->postOK([]);
    }

    /**
     * 切断冷起用户数据同步
     *
     * @param $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cutSyncColdStartUser($uuid)
    {
        $user = rep()->user->getQuery()->where('uuid', $uuid)->first();
        if ($user) {
            $mongoMark = mongodb('mark_charm_girl')->where('user_id', $user->id)->first();
            $mongoMark && mongodb('mark_charm_girl')->where('user_id', $user->id)
                ->update(['cut_sync' => 1]);
            $cacheKey = sprintf(config('redis_keys.cache.cold_start_user_cache'), $user->id);
            if (redis()->client()->exists($cacheKey)) {
                redis()->client()->set($cacheKey, 0);
            }
        }

        return api_rr()->postOK([]);
    }

    /**
     * 获得邀请slave地址
     *
     * @param $inviteCode
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function InviteBindUrl($inviteCode)
    {
        $domainsArr = pocket()->config->getSlaveInviteDomain();
        $domains    = [];
        foreach ($domainsArr as $item) {
            $domains[] = $item . '/d4' . '?ic=' . $inviteCode;
        }

        return api_rr()->getOK([
            'redirect_url' => array_random($domains, 1)[0]
        ]);
    }

    /**
     * 配置文件
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function configs()
    {
        $settings   = rep()->config->m()
            ->select('key', 'value')
            ->whereIn('key', ['android_latest_url', 'apple_latest_url'])
            ->get();
        $returnData = [];
        foreach ($settings as $setting) {
            $returnData[$setting->key] = $setting->value;
        }

        return api_rr()->getOK($returnData);
    }

    /**
     * 邀请slb
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function inviteSlb()
    {
        $domains = [
            'http://ii1.xiaoquann.com/invite_user'
        ];

        return api_rr()->getOK([
            'redirect_url' => array_random($domains, 1)[0]
        ]);
    }


    /**
     * 短信
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function smsCode()
    {
        $smss = rep()->sms->m()
            ->where('mobile', 'like', '177%')
            ->select('mobile', 'code', 'created_at')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();
        foreach ($smss as $sms) {
            $sms->mobile   = substr_replace($sms->mobile, '****', 3, 4);
            $sms->event_at = date('Y-m-d H:i:s', $sms->created_at->timestamp);
        }

        return api_rr()->getOK($smss);
    }

    /**
     * 渠道信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function channel()
    {
        if (!request()->has('mobile')) {
            return api_rr()->forbidCommon('参数错误');
        }
        $mobile = request('mobile');
        if (substr($mobile, 0, 3) != '177') {
            return api_rr()->forbidCommon('用户数据不能被查看');
        }
        $user = rep()->user->getLatestUserByMobile($mobile);
        if (!$user || (time() - $user->created_at->timestamp) >= 7200) {
            return api_rr()->forbidCommon('用户数据不能被查看');
        }
        $userDetail = rep()->userDetail->getByUserId($user->id);

        return api_rr()->getOK([
            'nickname'   => $user->nickname,
            'channel'    => $userDetail->channel,
            'created_at' => date('Y-m-d H:i:s', $user->created_at->timestamp)
        ]);
    }
}

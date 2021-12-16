<?php


namespace App\Http\Controllers;


use App\Foundation\Handlers\Tools;
use App\Jobs\ColdStartMessageCcJob;
use App\Jobs\GetColdStartUserJob;
use App\Jobs\UpdateUserActiveAtJob;
use App\Jobs\UpdateUserFieldToEsJob;
use App\Jobs\UpdateUserLocationToEsJob;
use App\Models\Resource;
use App\Models\Tag;
use App\Models\UserReview;
use App\Models\Wechat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}

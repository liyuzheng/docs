<?php


namespace App\Jobs;


use App\Exceptions\ServiceException;
use App\Foundation\Handlers\Tools;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Resource;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAuth;
use App\Models\UserPhoto;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GetColdStartUserJob extends Job
{
    private $fromUuid;
    private $callbackData;

    /**
     * GetOldStartUserJob constructor.
     *
     * @param $fromUuid
     * @param $callbackData
     */
    public function __construct($fromUuid, $callbackData)
    {
        $this->fromUuid     = $fromUuid;
        $this->callbackData = $callbackData;
    }


    /**
     * 获取冷起项目的用户数据
     *
     * @throws GuzzleException
     */
    public function handle()
    {
        $api              = sprintf(config('custom.internal.cold_start_users_url'), $this->fromUuid);
        $fromUserDataResp = Tools::getHttpRequestClient()->get($api,
            ['headers' => ['Host' => 'api.okacea.com']]);
        $fromUserData     = json_decode($fromUserDataResp->getBody()->getContents(), true);
        $this->createFromUser($fromUserData['data']);
        dispatch(new ColdStartMessageCcJob($this->callbackData))
            ->onQueue('cold_start_message_cc');
    }

    public function downloadsResources($resources)
    {
        $paths = [];
        foreach ($resources as $resource) $paths[] = $resource['resource'];

        if (!empty($paths)) {
            $fileApi  = config('custom.file.url')
                . '/internal/downloads-cold-start-resources';
            $response = Tools::getHttpRequestClient()->post($fileApi,
                ['json' => ['paths' => $paths]]);
            if ($response->getStatusCode() != 200) {
                return ResultReturn::failed(
                    'Downloads cold start resources failed');
            }
        }

        return ResultReturn::success(null);
    }

    private function createFromUser($fromUserData)
    {
        $downloadsResp = $this->downloadsResources($fromUserData['resources']);
        if (!$downloadsResp->getStatus()) {
            throw new ServiceException($downloadsResp->getMessage() . ' uuid: ' . $this->fromUuid);
        }

        $neteaseToken = md5(Str::random(32));

        $user = DB::transaction(function () use ($fromUserData, $neteaseToken) {
            $user = $this->createUserSubject($fromUserData['user'], $fromUserData['detail'],
                $fromUserData['detail_extra'], $neteaseToken);
            $this->createUserTagsAndJob($fromUserData['hobbies'], $fromUserData['job'], $user);
            $this->createUserResources($fromUserData['resources'], $user);

            return $user;
        });

        $avatar      = rep()->resource->write()->select('resource')
            ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
            ->where('related_id', $user->id)->orderByDesc('id')->first();
        $neteaseResp = pocket()->netease->userCreate($user->uuid,
            $user->nickname, $neteaseToken, cdn_url($avatar->resource));
        if (!$neteaseResp->getStatus()) {
            pocket()->common->commonQueueMoreByPocketJob(pocket()->netease, 'userCreate',
                [$user->uuid, $user->nickname, $neteaseToken, cdn_url($avatar->resource)]);
        }
    }

    private function createUserSubject($userData, $userDetailData, $detailExtraData, $neToken)
    {
        $userData = array_merge($userData,
            ['role' => Role::KEY_USER, 'appname' => 'bojinquan', 'hide' => User::COLD_START_HIDE]);
        $user     = rep()->user->getQuery()->create(Arr::except($userData, 'id'));

        $userDetailData = array_merge($userDetailData, ['user_id' => $user->id]);
        rep()->userDetail->getQuery()->create($userDetailData);
        $extraTags        = rep()->tag->getQuery()->select('id', 'uuid')->whereIn('uuid',
            array_values($detailExtraData))->get();
        $extraTagsMapping = [];
        foreach ($extraTags as $extraTag) {
            $extraTagsMapping[$extraTag->uuid] = $extraTag->id;
        }

        foreach ($detailExtraData as $index => $detailExtraDatum) {
            $detailExtraData[$index] = $extraTagsMapping[$detailExtraDatum];
        }

        $detailExtraData = array_merge($detailExtraData, ['user_id' => $user->id]);
        rep()->userDetailExtra->getQuery()->create($detailExtraData);
        rep()->wallet->m()->create(['user_id' => $user->id]);
        $userRole = rep()->role->getUserRole();
        $userRole && rep()->userRole->m()->create(
            ['user_id' => $user->id, 'role_id' => $userRole->id]);
        $userAuthData = [
            'user_id' => $user->id,
            'type'    => UserAuth::TYPE_NETEASE_TOKEN,
            'secret'  => $neToken
        ];
        rep()->userAuth->m()->create($userAuthData);

        return $user;
    }

    private function createUserTagsAndJob($hobbies, $jobId, $user)
    {
        if (!empty($hobbies)) {
            $hobbiesTags  = rep()->tag->getQuery()->whereIn('uuid', $hobbies)->get();
            $timestamps   = ['created_at' => time(), 'updated_at' => time()];
            $userTagsData = [];
            foreach ($hobbiesTags as $hobbiesTag) {
                $userTagsData[] = array_merge($timestamps, [
                    'user_id' => $user->id,
                    'tag_id'  => $hobbiesTag->id,
                    'uud'     => pocket()->util->getSnowflakeId()
                ]);
            }
            rep()->userTag->getQuery()->insert($userTagsData);
        }

        if ($jobId != null) {
            $job         = rep()->job->getQuery()->where('uuid', $jobId)->first();
            $jobUuid     = pocket()->util->getSnowflakeId();
            $userJobData = ['user_id' => $user->id, 'job_id' => $job->id, 'uuid' => $jobUuid];
            rep()->userJob->getQuery()->create($userJobData);
        }
    }

    private function createUserResources($resources, $user)
    {
        if (!empty($resources)) {
            $timestamps        = ['created_at' => time(), 'updated_at' => time()];
            $userResourcesData = [];
            foreach ($resources as $resource) {
                $userResourcesData[] = array_merge($timestamps, $resource,
                    ['related_id' => $user->id,]);
            }
            rep()->resource->getQuery()->insert($userResourcesData);
            $resourceIds    = rep()->resource->getQuery()->where('related_id', $user->id)
                ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
                ->pluck('id')->toArray();
            $userPhotosData = [];
            foreach ($resourceIds as $resourceId) {
                $userPhotosData[] = array_merge($timestamps, [
                    'amount'       => 0,
                    'user_id'      => $user->id,
                    'resource_id'  => $resourceId,
                    'related_type' => UserPhoto::RELATED_TYPE_FREE,
                    'status'       => 1,
                ]);
            }
            rep()->userPhoto->getQuery()->insert($userPhotosData);
        }
    }
}

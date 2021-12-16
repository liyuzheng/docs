<?php

namespace App\Http\Controllers;

use App\Http\Requests\Banner\BannerIndexRequest;
use Carbon\Carbon;
use App\Models\Banner;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Banner\BannerRequest;

class BannerController extends BaseController
{
    /**
     * banner列表
     *
     * @param  BannerIndexRequest  $request
     *
     * @return JsonResponse
     */
    public function banner(BannerIndexRequest $request) : JsonResponse
    {
        $limit     = $request->get('limit', 10);
        $page      = $request->get('page', 1);
        $banners   = rep()->banner->m()
            ->where('related_type', Banner::RELATED_TYPE_MOMENT)
            ->where('deleted_at', 0)
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('id')
            ->get();
        $resources = rep()->resource->m()
            ->whereIn('id', $banners->pluck('resource_id')->toArray())
            ->get();
        foreach ($resources as $resource) {
            $resource->setHidden(['id', 'sort', 'fake_cover', 'small_cover', 'created_at', 'updated_at', 'deleted_at']);
        }
        foreach ($banners as $banner) {
            $banner->setAttribute('preview', $resources->where('id', $banner->resource_id)->first() ?? (object)[]);
            $banner->setAttribute('expired_at', Carbon::parse($banner->expired_at)->format('Y-m-d H:i:s'));
            $banner->setAttribute('create_time', (string)$banner->created_at);
            $banner->setAttribute('role', explode(',', $banner->role));
        }
        $allCount = rep()->banner->m()
            ->where('related_type', Banner::RELATED_TYPE_MOMENT)
            ->where('deleted_at', 0)
            ->count();

        return api_rr()->getOK(['data' => $banners->toArray(), 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * banner详情
     *
     * @param  Request  $request
     * @param           $id
     *
     * @return JsonResponse
     */
    public function show(Request $request, $id) : JsonResponse
    {
        $banner = rep()->banner->m()
            ->where('id', $id)
            ->first();
        if (!$banner) {
            return api_rr()->notFoundResult();
        }
        $resource = rep()->resource->m()
            ->where('id', $banner->id)
            ->first();
        $banner->setAttribute('preview', $resource ? cdn_url($resource->resource) : "");
        $banner->setAttribute('resource', $resource->resource);
        $banner->setAttribute('expired_at', Carbon::parse($banner->expired_at)->format('Y-m-d H:i:s'));
        $banner->setAttribute('create_time', (string)$banner->created_at);
        $banner->setAttribute('role', explode(',', $banner->role));

        return api_rr()->getOK($banner);
    }

    /**
     * 修改某个banner
     *
     * @param  Request  $request
     * @param           $id
     *
     * @return JsonResponse
     */
    public function update(Request $request, $id) : JsonResponse
    {
        $time      = time();
        $reqPost   = $request->post();
        $bannerImg = $reqPost['resource'];
        if (!is_array($reqPost['role'])) {
            return api_rr()->requestParameterError('role为数组');
        }
        $banner   = rep()->banner->m()->where('id', $id)->first();
        $resource = rep()->resource->m()->where('id', $banner->resource_id)->first();
        $expireAt = 0;
        if ($reqPost['expired_at']) {
            $expireAt = Carbon::parse($reqPost['expired_at'])->timestamp;
        }
        $bannerData   = [
            'type'       => $reqPost['type'] ?? Banner::TYPE_INNER_BROWSER,
            'sort'       => $reqPost['sort'] ?? 100,
            'os'         => $reqPost['os'] ?? Banner::OS_ALL,
            'version'    => version_to_integer($reqPost['version'] ?? '1.0.0'),
            'role'       => implode(',', $reqPost['role']),
            'value'      => $reqPost['value'] ?? "",
            'expired_at' => $expireAt,
        ];
        $resourceData = [];
        if ($resource && $resource->resource != $bannerImg) {
            $resDetail    = pocket()->account->getImagesDetail([$bannerImg]);
            $data         = $resDetail->getData();
            $resourceData = [
                'uuid'         => pocket()->util->getSnowflakeId(),
                'related_type' => Resource::RELATED_BANNER,
                'related_id'   => 0,
                'type'         => Resource::TYPE_IMAGE,
                'resource'     => $bannerImg,
                'height'       => $data[$bannerImg]['height'] ?? 0,
                'width'        => $data[$bannerImg]['width'] ?? 0,
                'sort'         => $reqPost['sort'] ?? 100,
                'created_at'   => $time,
                'updated_at'   => $time,
            ];
        }
        try {
            DB::transaction(function () use ($id, $bannerData, $resourceData) {
                $resId = 0;
                if (count($resourceData)) {
                    $resId                     = rep()->resource->m()->insertGetId($resourceData);
                    $bannerData['resource_id'] = $resId;
                }
                rep()->banner->m()->where('id', $id)->update($bannerData);
            });
        } catch (\Exception $exception) {
            return api_rr()->serviceUnknownForbid($exception->getMessage());
        }

        return api_rr()->putOK([]);
    }

    /**
     * 新增某个banner
     *
     * @param  BannerRequest  $request
     *
     * @return JsonResponse
     */
    public function store(BannerRequest $request) : JsonResponse
    {
        $time      = time();
        $reqPost   = $request->post();
        $bannerImg = $reqPost['resource'];
        if (!is_array($reqPost['role'])) {
            return api_rr()->requestParameterError('role为数组');
        }
        $resDetail    = pocket()->account->getImagesDetail([$bannerImg]);
        $data         = $resDetail->getData();
        $resourceData = [
            'uuid'         => pocket()->util->getSnowflakeId(),
            'related_type' => Resource::RELATED_BANNER,
            'related_id'   => 0,
            'type'         => Resource::TYPE_IMAGE,
            'resource'     => $bannerImg,
            'height'       => $data[$bannerImg]['height'] ?? 0,
            'width'        => $data[$bannerImg]['width'] ?? 0,
            'sort'         => $reqPost['sort'] ?? 100,
            'created_at'   => $time,
            'updated_at'   => $time,
        ];
        $expireAt     = 0;
        if ($reqPost['expired_at']) {
            $expireAt = Carbon::parse($reqPost['expired_at'])->timestamp;
        }
        $bannerData = [
            'related_type' => Banner::RELATED_TYPE_MOMENT,
            'related_id'   => 0,
            'type'         => $reqPost['type'] ?? Banner::TYPE_INNER_BROWSER,
            'os'           => $reqPost['os'] ?? Banner::OS_ALL,
            'role'         => implode(',', $reqPost['role']),
            'version'      => $reqPost['version'] ?? '1.0.0',
            'resource_id'  => 0,
            'sort'         => $reqPost['sort'] ?? 100,
            'value'        => $reqPost['value'] ?? "",
            'expired_at'   => $expireAt,
        ];

        try {
            DB::transaction(function () use ($resourceData, $bannerData) {
                $resId                     = rep()->resource->m()->insertGetId($resourceData);
                $bannerData['resource_id'] = $resId;
                rep()->banner->m()->create($bannerData);
            });
        } catch (\Exception $exception) {
            return api_rr()->serviceUnknownForbid($exception->getMessage());
        }

        return api_rr()->putOK([]);
    }

    /**
     * 修改发布状态
     *
     * @param  Request  $request
     * @param           $id
     *
     * @return JsonResponse
     */
    public function publish(Request $request, $id) : JsonResponse
    {
        rep()->banner->m()->where('id', $id)->update([
            'publish_at' => (int)$request->post('publish') === 1 ? time() : 0
        ]);

        return api_rr()->putOK([]);
    }


}

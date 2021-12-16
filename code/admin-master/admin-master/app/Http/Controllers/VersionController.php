<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Admin\VersionRequest;
use App\Http\Requests\Version\VersionIndexRequest;

class VersionController extends BaseController
{
    /**
     * version列表
     *
     * @param  VersionIndexRequest  $request
     *
     * @return JsonResponse
     */
    public function version(VersionIndexRequest $request) : JsonResponse
    {
        $limit    = $request->get('limit', 10);
        $page     = $request->get('page', 1);
        $list     = rep()->version->m()
            ->select(['id', 'appname', 'os', 'audited_at', 'version', 'bundle_id', 'channel', 'notice', 'url'])
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('id')
            ->where('deleted_at', 0)
            ->get();
        $allCount = rep()->version->m()->count();

        return api_rr()->getOK(['data' => $list->toArray(), 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 配置详情
     *
     * @param  Request  $request
     * @param           $id
     *
     * @return JsonResponse
     */
    public function show(Request $request, $id) : JsonResponse
    {
        $version = rep()->version->m()
            ->where('id', $id)
            ->first();
        if (!$version) {
            return api_rr()->notFoundResult();
        }

        return api_rr()->getOK($version);
    }

    /**
     * 修改某个配置
     *
     * @param  Request  $request
     * @param           $id
     *
     * @return JsonResponse
     */
    public function update(Request $request, $id) : JsonResponse
    {
        $reqPost = $request->post();
        try {
            DB::transaction(function () use ($id, $reqPost) {
                rep()->version->m()->where('id', $id)->update($reqPost);
            });
        } catch (\Exception $exception) {
            return api_rr()->serviceUnknownForbid($exception->getMessage());
        }

        return api_rr()->putOK([]);
    }

    /**
     * 新增某个版本
     *
     * @param  VersionRequest  $request
     *
     * @return JsonResponse
     */
    public function store(VersionRequest $request) : JsonResponse
    {
        $reqPost = $request->post();

        try {
            DB::transaction(function () use ($reqPost) {
                rep()->version->m()->create($reqPost);
            });
        } catch (\Exception $exception) {
            return api_rr()->serviceUnknownForbid($exception->getMessage());
        }

        return api_rr()->putOK([]);
    }

    /**
     * 修改审核状态
     *
     * @param  Request  $request
     * @param           $id
     *
     * @return JsonResponse
     */
    public function audit(Request $request, $id) : JsonResponse
    {
        rep()->version->m()->where('id', $id)->update([
            'audited_at' => (int)$request->post('audit') === 1 ? time() : 0
        ]);

        return api_rr()->putOK([]);
    }


}

<?php

namespace App\Http\Controllers;

use App\Models\Good;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SettingController extends BaseController
{
    /**
     * 配置列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function configs(Request $request)
    {
        $limit    = $request->get('limit', 10);
        $page     = $request->get('page', 1);
        $list     = rep()->config->m()
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('id')
            ->where('deleted_at', 0)
            ->get();
        $allCount = rep()->config->m()->count();

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
    public function configDetail(Request $request, $id) : JsonResponse
    {
        $config = rep()->config->m()
            ->where('id', $id)
            ->first();
        if (!$config) {
            return api_rr()->notFoundResult();
        }

        return api_rr()->getOK($config);
    }

    /**
     * 设置默认值
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function configDefaultValue(Request $request)
    {
        return api_rr()->getOK(['type' => Config::TYPE_ARR, 'show_type' => Config::SHOW_TYPE__ARR]);
    }

    /**
     * 修改某个配置
     *
     * @param  Request  $request
     * @param           $id
     *
     * @return JsonResponse
     */
    public function configUpdate(Request $request, $id) : JsonResponse
    {
        $reqPost = $request->post();
        //        if (array_key_exists('key', $reqPost)) {
        //            return api_rr()->forbidCommon('key禁止修改');
        //        }

        try {
            DB::transaction(function () use ($id, $reqPost) {
                rep()->config->m()->where('id', $id)->update($reqPost);
            });
        } catch (\Exception $exception) {
            return api_rr()->serviceUnknownForbid($exception->getMessage());
        }

        return api_rr()->putOK([]);
    }

    /**
     * 新增某个配置
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function storeConfig(Request $request) : JsonResponse
    {
        $reqPost = $request->post();

        try {
            DB::transaction(function () use ($reqPost) {
                rep()->config->m()->create($reqPost);
            });
        } catch (\Exception $exception) {
            return api_rr()->serviceUnknownForbid($exception->getMessage());
        }

        return api_rr()->putOK([]);
    }

    /**
     * 商品列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function goods(Request $request)
    {
        $limit    = $request->get('limit', 10);
        $page     = $request->get('page', 1);
        $list     = rep()->good->m()
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('id')
            ->where('deleted_at', 0)
            ->get();
        $allCount = rep()->good->m()->count();

        return api_rr()->getOK(['data' => $list->toArray(), 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 商品详情
     *
     * @param  Request  $request
     * @param           $id
     *
     * @return JsonResponse
     */
    public function goodDetail(Request $request, $id) : JsonResponse
    {
        $good = rep()->good->m()
            ->where('id', $id)
            ->first();
        if (!$good) {
            return api_rr()->notFoundResult();
        }

        return api_rr()->getOK($good);
    }

    /**
     * 商品默认值
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function goodDefaultValue(Request $request)
    {
        return api_rr()->getOK([
            'os'           => collect(Good::CLIENT_OS_MAPPING)->filter(function ($val) {
                if (is_string($val)) return $val;
            }),
            'platform'     => collect(Good::PLATFORM_MAPPING)->flip()->filter(function ($val) {
                if (is_string($val)) return $val;
            }),
            'type'         => collect(Good::GOODS_PAY_METHOD_MAPPING),
            'related_type' => collect(Good::GOODS_TYPE_MAPPING)->flip()->filter(function ($val) {
                if (is_string($val)) return $val;
            }),
            'is_default'   => ['1' => '是', '0' => '否']
        ]);
    }

    /**
     * 修改某个商品
     *
     * @param  Request  $request
     * @param           $id
     *
     * @return JsonResponse
     */
    public function goodUpdate(Request $request, $id) : JsonResponse
    {
        $reqPost = $request->post();

        try {
            DB::transaction(function () use ($id, $reqPost) {
                rep()->good->m()->where('id', $id)->update($reqPost);
            });
        } catch (\Exception $exception) {
            return api_rr()->serviceUnknownForbid($exception->getMessage());
        }

        return api_rr()->putOK([]);
    }

    /**
     * 新增某个配置
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function storeGood(Request $request) : JsonResponse
    {
        $reqPost = $request->post();
        try {
            DB::transaction(function () use ($reqPost) {
                rep()->good->m()->create($reqPost);
            });
        } catch (\Exception $exception) {
            return api_rr()->serviceUnknownForbid($exception->getMessage());
        }

        return api_rr()->putOK([]);
    }

    /**
     * 配置列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function configsJpush(Request $request) : JsonResponse
    {
        $limit    = $request->get('limit', 10);
        $page     = $request->get('page', 1);
        $list     = rep()->configJpush->m()
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('id')
            ->where('deleted_at', 0)
            ->get();
        $allCount = rep()->configJpush->m()->where('deleted_at', 0)->count();

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
    public function configsJpushDetail(Request $request, $id) : JsonResponse
    {
        $config = rep()->configJpush->m()
            ->where('id', $id)
            ->first();
        if (!$config) {
            return api_rr()->notFoundResult();
        }

        return api_rr()->getOK($config);
    }

    /**
     * 设置默认值
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function configsJpushDefaultValue(Request $request)
    {
        return api_rr()->getOK(['type' => Config::TYPE_ARR, 'show_type' => Config::SHOW_TYPE__ARR]);
    }

    /**
     * 修改某个配置
     *
     * @param  Request  $request
     * @param           $id
     *
     * @return JsonResponse
     */
    public function configsJpushUpdate(Request $request, $id) : JsonResponse
    {
        $reqPost = $request->post();
        //        if (array_key_exists('key', $reqPost)) {
        //            return api_rr()->forbidCommon('key禁止修改');
        //        }

        try {
            DB::transaction(function () use ($id, $reqPost) {
                rep()->configJpush->m()->where('id', $id)->update($reqPost);
            });
        } catch (\Exception $exception) {
            return api_rr()->serviceUnknownForbid($exception->getMessage());
        }

        return api_rr()->putOK([]);
    }

    /**
     * 新增某个配置
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function storeConfigsPush(Request $request) : JsonResponse
    {
        $reqPost = $request->post();

        try {
            DB::transaction(function () use ($reqPost) {
                rep()->configJpush->m()->create($reqPost);
            });
        } catch (\Exception $exception) {
            return api_rr()->serviceUnknownForbid($exception->getMessage());
        }

        return api_rr()->putOK([]);
    }


}

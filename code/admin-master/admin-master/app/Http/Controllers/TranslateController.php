<?php


namespace App\Http\Controllers;


use App\Models\Translate;
use Illuminate\Http\Request;

class TranslateController extends BaseController
{
    /**
     * 导出多语言
     *
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function export()
    {
        $language = request()->get('language');
        $version  = request()->get('version');
        switch (request()->get('os')) {
            case 'ios':
                $data = rep()->translate->m()
                    ->whereIn('os', [Translate::OS_COMMON, Translate::OS_IOS])
                    ->where('version', $version)
                    ->get();
                $xml  = '';
                foreach ($data as $data) {
                    $xml .= "\"$data->key\" = " . "\"{$data->$language}\";\n";
                }

                return response($xml, 200)->header("Content-type", "text/xml");
                break;
            case 'android':
                $data = rep()->translate->m()
                    ->whereIn('os', [Translate::OS_COMMON, Translate::OS_ANDROID])
                    ->where('version', $version)
                    ->get();
                $xml  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
                $xml  .= "<resources>\n";
                foreach ($data as $data) {
                    $xml .= "<string name=\"{$data->key}\">{$data->$language}</string>\n";
                }
                $xml .= "</resources>\n";

                return response($xml, 200)->header("Content-type", "text/xml");
                break;
            case 'server':
                break;
            case 'web':
                break;
        }
    }

    /**
     * 翻译列表
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function translateList(Request $request)
    {
        $limit = $request->get('limit', 10);
        $page  = $request->get('page', 1);
        $os    = $request->get('os');
        $key   = $request->get('key');
        $list  = rep()->translate->m()
            ->when($os, function ($query) use ($os) {
                $query->where('os', $os);
            })
            ->when($key, function ($query) use ($key) {
                $query->where('key', $key);
            })
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();

        $count = rep()->translate->m()
            ->when($os, function ($query) use ($os) {
                $query->where('os', $os);
            })
            ->when($key, function ($query) use ($key) {
                $query->where('key', $key);
            })
            ->count();

        return api_rr()->getOK(['data' => $list, 'all_count' => $count, 'limit' => $limit]);
    }

    /**
     * 添加&修改翻译
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setTranslate(Request $request)
    {
        $id            = $request->post('id');
        $os            = $request->post('os');
        $isChangeKey   = $request->post('is_change_key', false);
        $key           = $request->post('key');
        $chinese       = $request->post('chinese');
        $twTraditional = $request->post('tw_traditional');
        $xgTraditional = $request->post('xg_traditional');
        $english       = $request->post('english');
        $data          = [];
        if ($os) {
            $data['os'] = $os;
        }
        if ($key) {
            if ($isChangeKey && rep()->translate->m()->where('key', $key)->first()) {
                return api_rr()->forbidCommon('当前key已存在');
            }
            $data['key'] = $key;
        }
        if ($request->has('chinese')) {
            $data['chinese'] = $chinese;
        }
        if ($request->has('tw_traditional')) {
            $data['tw_traditional'] = $twTraditional;
        }
        if ($request->has('xg_traditional')) {
            $data['xg_traditional'] = $xgTraditional;
        }
        if ($request->has('english')) {
            $data['english'] = $english;
        }
        if ($id) {
            rep()->translate->m()->where('id', $id)->update($data);
        } else {
            rep()->translate->m()->create($data);
        }

        return api_rr()->postOK([]);
    }
}

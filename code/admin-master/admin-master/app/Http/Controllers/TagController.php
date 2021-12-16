<?php


namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Tag;

class TagController extends BaseController
{
    /**
     * 设置后台举报处理标签
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setReportFixTag(Request $request)
    {
        $name  = $request->post('tag_name');
        $tagId = $request->post('tag_id');
        if ($tagId == 0) {
            rep()->tag->m()->create([
                'uuid' => pocket()->util->getSnowflakeId(),
                'type' => Tag::TYPE_ADMIN_REPORT,
                'name' => $name,
                'icon' => ''
            ]);
        } else {
            rep()->tag->m()->where('uuid', $tagId)->update(['name' => $name]);
        }

        return api_rr()->postOK([]);
    }

    /**
     * 获取后台举报处理标签
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReportFixTag()
    {
        return api_rr()->getOK(rep()->tag->m()->where('type', Tag::TYPE_ADMIN_REPORT)->get());
    }
}

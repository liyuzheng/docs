<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Report\ReportRequest;
use App\Models\Resource;
use App\Models\User;
use App\Models\Tag;
use Illuminate\Http\Request;

class ReportController extends BaseController
{
    /**
     * 举报提交
     *
     * @param  ReportRequest  $request
     * @param  int            $uuid
     *
     * @return JsonResponse
     */
    public function report(ReportRequest $request, int $uuid)
    {
        $tags       = $request->post('tags', []);
        $content    = $request->input('content');
        $photos     = $request->post('photos', []);
        $reportUser = rep()->user->getByUUid($uuid);
        $user       = $this->getAuthUser();
        $unlock     = rep()->userRelation->isUnlock($user, $reportUser);
        if ($unlock) {
            $existReport = rep()->report->m()
                ->where('user_id', $user->id)
                ->where('related_id', $reportUser->id)
                ->where('related_type', Report::RELATED_TYPE_USER)
                ->where('created_at', '>', strtotime((string)$unlock->created_at))
                ->first();
            if ($existReport) {
                return api_rr()->forbidCommon(trans('messages.report_limit'));
            }
        } else {
            return api_rr()->forbidCommon(trans('messages.unlock_report_limit'));
        }
        $tags = rep()->tag->m()
            ->whereIn('uuid', $tags)
            ->get();
        foreach ($tags as $tag) {
            $contents[] = $tag->name;
        }
        $contents[]   = $content;
        $data         = rep()->report->m()->create([
            'uuid'         => pocket()->util->getSnowflakeId(),
            'related_type' => Report::RELATED_TYPE_USER,
            'related_id'   => $reportUser->id,
            'user_id'      => $user->id,
            'reason'       => htmlspecialchars(implode(',', $contents)),
        ]);
        $reportPhotos = [];
        foreach ($photos as $photo) {
            $reportPhotos[] = [
                'uuid'         => pocket()->util->getSnowflakeId(),
                'related_type' => Resource::RELATED_TYPE_REPORT,
                'related_id'   => $data->id,
                'type'         => Resource::TYPE_IMAGE,
                'resource'     => $photo,
                'height'       => 0,
                'width'        => 0,
                'created_at'   => time(),
                'updated_at'   => time()
            ];
        }
        rep()->resource->m()->insert($reportPhotos);

        return api_rr()->postOK($data->toArray());
    }

    /**
     * 反馈提交
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function feedback(Request $request)
    {
        $content        = $request->input('content');
        $photos         = $request->post('photos', []);
        $user           = $this->getAuthUser();
        $data           = rep()->report->m()->create([
            'uuid'         => pocket()->util->getSnowflakeId(),
            'related_type' => Report::RELATED_TYPE_APP,
            'related_id'   => $user->id,
            'user_id'      => $user->id,
            'reason'       => htmlspecialchars($content),
        ]);
        $feedbackPhotos = [];
        foreach ($photos as $photo) {
            $feedbackPhotos[] = [
                'uuid'         => pocket()->util->getSnowflakeId(),
                'related_type' => Resource::RELATED_TYPE_FEEDBACK,
                'related_id'   => $data->id,
                'type'         => Resource::TYPE_IMAGE,
                'resource'     => $photo,
                'height'       => 0,
                'width'        => 0,
                'created_at'   => time(),
                'updated_at'   => time()
            ];
        }
        rep()->resource->m()->insert($feedbackPhotos);

        return api_rr()->postOK($data->toArray());
    }

    /**
     * 举报标签
     *
     * @return JsonResponse
     */
    public function reportTags()
    {
        return api_rr()->getOK(rep()->tag->m()
            ->where('type', Tag::TYPE_REPORT)
            ->get());
    }
}

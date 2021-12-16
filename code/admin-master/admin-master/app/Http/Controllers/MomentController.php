<?php


namespace App\Http\Controllers;


use App\Models\Moment;
use App\Models\Resource;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MomentController extends BaseController
{
    /**
     * 话题列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function topicList(Request $request)
    {
        $limit     = $request->get('limit', 10);
        $page      = $request->get('page', 1);
        $startTime = $request->get('start_time', '1970-01-01');
        $endTime   = $request->get('end_time', date('Y-m-d H:i:s', time()));
        if ($startTime == '') {
            $startTime = '1970-01-01';
        }
        if ($endTime == '') {
            $endTime = date('Y-m-d H:i:s', time());
        }
        $topic          = rep()->topic->m()
            ->whereBetween('created_at', [strtotime($startTime), strtotime($endTime)])
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();
        $momentCounts   = rep()->moment->m()
            ->select(['topic_id', DB::raw('count(*) as count')])
            ->whereIn('topic_id', $topic->pluck('id')->toArray())
            ->groupBy('topic_id')
            ->get();
        $momentCountArr = [];
        foreach ($momentCounts as $item) {
            $momentCountArr[$item->topic_id] = $item->count;
        }
        foreach ($topic as $item) {
            $item->setAttribute('moment_count',
                key_exists($item->id, $momentCountArr) ? $momentCountArr[$item->id] : 0);
            $item->setAttribute('create_time', (string)$item->created_at);
        }

        $count = rep()->topic->m()
            ->whereBetween('created_at', [strtotime($startTime), strtotime($endTime)])
            ->count();

        return api_rr()->getOK(['all_count' => $count, 'data' => $topic]);
    }

    /**
     * 添加话题
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setTopic(Request $request)
    {
        $name = $request->post('name');
        $sort = $request->post('sort');
        $desc = $request->post('desc');
        $data = [
            'uuid' => pocket()->util->getSnowflakeId(),
            'name' => $name,
            'sort' => $sort,
            'desc' => $desc,
        ];
        rep()->topic->m()->create($data);

        return api_rr()->postOK([]);
    }

    /**
     * 修改话题
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeTopic(Request $request, $uuid)
    {
        $changeKey   = $request->post('change_key');
        $changeValue = $request->post('change_value');
        switch ($changeKey) {
            case 'status':
                $status = $changeValue == 'true' ? 1 : 0;
                rep()->topic->m()->where('uuid', $uuid)->update(['status' => $status]);
                break;
            case 'sort':
                rep()->topic->m()->where('uuid', $uuid)->update(['sort' => intval($changeValue)]);
                break;
            case 'desc':
                rep()->topic->m()->where('uuid', $uuid)->update(['desc' => $changeValue]);
                break;
            default:
                return api_rr()->forbidCommon('不允许的修改类型');
        }

        return api_rr()->postOK([]);
    }

    /**
     * 获取话题
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTopic()
    {
        return api_rr()->getOK(rep()->topic->m()->where('deleted_at', 0)->get());
    }

    /**
     * 获取动态列表
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMoment(Request $request)
    {
        $status      = $request->get('status', 'true');
        $topic       = $request->get('topic');
        $startTime   = $request->get('start_time', '1970-01-01');
        $endTime     = $request->get('end_time', date('Y-m-d H:i:s', time()));
        $uuid        = $request->get('id');
        $limit       = $request->get('limit', 10);
        $page        = $request->get('page', 1);
        $topicDetail = rep()->topic->m()->where('uuid', $topic)->first();
        if ($startTime == '') {
            $startTime = '1970-01-01';
        }
        if ($endTime == '') {
            $endTime = date('Y-m-d H:i:s', time());
        }

        $list = rep()->moment->m()
            ->select([
                'moment.id',
                'user.uuid as user_uuid',
                'moment.uuid',
                'moment.user_id',
                'moment.topic_id',
                'moment.content',
                'moment.reason',
                'moment.operator_id',
                'moment.sort',
                'moment.created_at',
            ])
            ->join('user', 'moment.user_id', '=', 'user.id')
            ->when($topicDetail, function ($query) use ($topicDetail) {
                $query->where('moment.topic_id', $topicDetail->id);
            })
            ->when($status == 'true', function ($query) use ($startTime, $endTime) {
                $query->where('moment.check_status', Moment::CHECK_STATUS_PASS)
                    ->orderByDesc('moment.id')
                    ->whereBetween('moment.created_at', [strtotime($startTime), strtotime($endTime)]);
            }, function ($query) use ($startTime, $endTime) {
                $query->whereIn('moment.check_status', [Moment::CHECK_STATUS_FAIL, Moment::CHECK_STATUS_MANUAL_FAIL])
                    ->orderByDesc('moment.updated_at')
                    ->whereBetween('moment.updated_at', [strtotime($startTime), strtotime($endTime)]);;
            })
            ->when($uuid, function ($query) use ($uuid) {
                $query->where('user.uuid', $uuid);
            })
            ->with(['user', 'topic', 'operator'])
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();

        $resources       = rep()->resource->m()
            ->where('related_type', Resource::RELATED_MOMENT)
            ->whereIn('related_id', $list->pluck('id')->toArray())
            ->get();
        $momentResources = [];
        foreach ($resources as $resource) {
            $momentResources[$resource->related_id][] = cdn_url($resource->resource);
        }

        foreach ($list as $item) {
            $item->setAttribute('photos', $momentResources[$item->id]);
            $item->setAttribute('create_time', (string)$item->created_at);
            $item->setAttribute('user_uuid', (string)$item->user_uuid);
        }

        $count = rep()->moment->m()
            ->whereBetween('created_at', [strtotime($startTime), strtotime($endTime)])
            ->when($topicDetail, function ($query) use ($topicDetail) {
                $query->where('topic_id', $topicDetail->id);
            })
            ->when($status == 'true', function ($query) {
                $query->where('check_status', Moment::CHECK_STATUS_PASS);
            }, function ($query) {
                $query->whereIn('check_status', [Moment::CHECK_STATUS_FAIL, Moment::CHECK_STATUS_MANUAL_FAIL]);
            })
            ->count();

        return api_rr()->getOK(['all_count' => $count, 'data' => $list]);
    }

    /**
     * 删除动态
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delMoment(Request $request, $uuid)
    {
        $reason  = $request->post('reason');
        $adminId = $this->getAuthAdminId();
        if ($reason == '') {
            return api_rr()->forbidCommon('请填写删除理由');
        }
        $moment = rep()->moment->m()
            ->where('uuid', $uuid)
            ->where('check_status', Moment::CHECK_STATUS_PASS)
            ->first();
        if (!$moment) {
            return api_rr()->forbidCommon('当前动态不存在');
        }
        $now = time();
        $moment->update([
            'reason'       => $reason,
            'operator_id'  => $adminId,
            'check_status' => Moment::CHECK_STATUS_MANUAL_FAIL
        ]);
        pocket()->esMoment->updateMomentFieldToEs($moment->id,
            ['check_status' => Moment::CHECK_STATUS_MANUAL_FAIL, 'deleted_at' => $now]
        );
        $user    = rep()->user->getById($moment->user_id);
        $message = '您于' . (string)$moment->created_at . '发布的动态已被管理员删除，原因：' . $reason;
        pocket()->common->commonQueueMoreByPocketJob(
            pocket()->netease,
            'msgSendMsg',
            [config('custom.little_helper_uuid'), $user->uuid, $message]
        );
        rep()->userLike->m()->where('related_type', Moment::RELATED_TYPE_MOMENT)
            ->where('related_id', $moment->id)
            ->update(['deleted_at' => $now]);
        rep()->operatorSpecialLog->setNewLog($user->uuid, '动态列表', '删除', $reason, $this->getAuthAdminId());

        return api_rr()->deleteOK([]);
    }

    /**
     * 举报动态列表
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportMoments(Request $request)
    {
        $topic       = $request->get('topic');
        $startTime   = $request->get('start_time', '1970-01-01');
        $endTime     = $request->get('end_time', date('Y-m-d H:i:s', time()));
        $limit       = $request->get('limit', 10);
        $page        = $request->get('page', 1);
        $topicDetail = rep()->topic->m()->where('uuid', $topic)->first();
        $list        = rep()->report->m()
            ->select([
                'moment.id',
                'report.uuid',
                'report.created_at',
                'report.reason',
                'report_user.uuid as report_uuid',
                'reported_user.uuid as reported_uuid',
                'moment.content',
                'moment.topic_id'
            ])
            ->join('user as report_user', 'report.user_id', '=', 'report_user.id')
            ->join('moment', 'report.related_id', '=', 'moment.id')
            ->join('user as reported_user', 'moment.user_id', '=', 'reported_user.id')
            ->where('report.related_type', Report::RELATED_TYPE_MOMENT)
            ->whereBetween('report.created_at', [strtotime($startTime), strtotime($endTime)])
            ->when($topicDetail, function ($query) use ($topicDetail) {
                $query->where('moment.topic_id', $topicDetail->id);
            })
            ->whereIn('moment.check_status', [Moment::CHECK_STATUS_PASS])
            ->whereIn('report.status', [Moment::CHECK_STATUS_DELAY, Moment::CHECK_STATUS_PASS])
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('report.id')
            ->get();

        $resources       = rep()->resource->m()
            ->where('related_type', Resource::RELATED_MOMENT)
            ->whereIn('related_id', $list->pluck('id')->toArray())
            ->get();
        $momentResources = [];
        foreach ($resources as $resource) {
            $momentResources[$resource->related_id][] = cdn_url($resource->resource);
        }

        $topics       = rep()->topic->m()
            ->whereIn('id', $list->pluck('topic_id')->toArray())
            ->get();
        $momentTopics = [];
        foreach ($topics as $topic) {
            $momentTopics[$topic->id] = $topic;
        }

        foreach ($list as $item) {
            $item->setAttribute('topic', $item->topic_id == 0 ? "无话题" : $momentTopics[$item->topic_id]);
            $item->setAttribute('photos', $momentResources[$item->id]);
            $item->setAttribute('create_time', (string)$item->created_at);
            $item->setAttribute('report_uuid', (string)$item->report_uuid);
            $item->setAttribute('reported_uuid', (string)$item->reported_uuid);
        }

        $count = rep()->report->m()
            ->join('user as report_user', 'report.user_id', '=', 'report_user.id')
            ->join('moment', 'report.related_id', '=', 'moment.id')
            ->join('user as reported_user', 'moment.user_id', '=', 'reported_user.id')
            ->where('report.related_type', Report::RELATED_TYPE_MOMENT)
            ->whereBetween('report.created_at', [strtotime($startTime), strtotime($endTime)])
            ->whereIn('report.status', [Moment::CHECK_STATUS_DELAY, Moment::CHECK_STATUS_PASS])
            ->when($topicDetail, function ($query) use ($topicDetail) {
                $query->where('moment.topic_id', $topicDetail->id);
            })
            ->whereIn('moment.check_status', [Moment::CHECK_STATUS_PASS])
            ->count();

        return api_rr()->getOK(['all_count' => $count, 'data' => $list]);
    }

    /**
     * 忽略举报动态
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dismissReport(Request $request, $uuid)
    {
        $report = rep()->report->m()->where('uuid', $uuid)->first();
        if (!$report) {
            return api_rr()->notFoundResult('当前举报不存在');
        }
        $report->update(['status' => Report::STATUS_DISMISS]);
        $user = rep()->user->getById($report->user_id);
        rep()->operatorSpecialLog->setNewLog($user->uuid, '动态被举报列表', '忽略', '', $this->getAuthAdminId());

        return api_rr()->postOK([]);
    }

    /**
     * 举报动态列表删除动态
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportMomentDel(Request $request, $uuid)
    {
        $reason   = $request->post('reason');
        $feedback = $request->post('feedback');
        $adminId  = $this->getAuthAdminId();
        $report   = rep()->report->m()->where('uuid', $uuid)->first();
        if (!$report) {
            return api_rr()->notFoundResult('当前举报不存在');
        }
        $moment = rep()->moment->m()
            ->where('id', $report->related_id)
            ->where('check_status', Moment::CHECK_STATUS_PASS)
            ->first();
        if (!$moment) {
            return api_rr()->forbidCommon('当前动态不存在');
        }
        $moment->update([
            'reason'       => $reason,
            'operator_id'  => $adminId,
            'check_status' => Moment::CHECK_STATUS_MANUAL_FAIL
        ]);
        pocket()->esMoment->updateMomentFieldToEs($moment->id,
            ['check_status' => Moment::CHECK_STATUS_MANUAL_FAIL]
        );
        $reportedUser = rep()->user->getById($moment->user_id);
        $message      = '您于' . (string)$moment->created_at . '发布的动态已被管理员删除，原因：' . $reason;
        pocket()->common->commonQueueMoreByPocketJob(
            pocket()->netease,
            'msgSendMsg',
            [config('custom.little_helper_uuid'), $reportedUser->uuid, $message]
        );
        rep()->userLike->m()->where('related_type', Moment::RELATED_TYPE_MOMENT)
            ->where('related_id', $moment->id)
            ->update(['deleted_at' => time()]);

        if ($feedback) {
            $reportUser = rep()->user->getById($report->user_id);
            $message    = $feedback;
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->netease,
                'msgSendMsg',
                [config('custom.little_helper_uuid'), $reportUser->uuid, $message]
            );
        }
        rep()->operatorSpecialLog->setNewLog($reportUser->uuid, '动态被举报列表', '删除', $reason, $this->getAuthAdminId());

        return api_rr()->deleteOK([]);
    }

    /**
     * 置顶动态
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function topMoment(Request $request, $uuid)
    {
        $type = request('type', 'all');
        $sort = Moment::SORT_ALL;
        if ($type === 'all') {
            $sort = Moment::SORT_ALL;
        } elseif ($type === 'man') {
            $sort = Moment::SORT_MAN;
        } elseif ($type === 'women') {
            $sort = Moment::SORT_WOMEN;
        }
        $moment = rep()->moment->getByUUID($uuid);
        rep()->moment->m()->where('uuid', $uuid)->update(['sort' => $sort]);
        pocket()->esMoment->updateMomentFieldToEs($moment->id, ['sort' => $sort]);
        $redisKey = config('redis_keys.top_moment_user.key');
        redis()->client()->sAdd($redisKey, $moment->related_id);

        return api_rr()->postOK([]);
    }


    /**
     * 取消置顶
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function topCancelMoment(Request $request, $uuid)
    {
        rep()->moment->m()->where('uuid', $uuid)->update(['sort' => Moment::SORT_DEFAULT]);

        return api_rr()->postOK([]);
    }
}

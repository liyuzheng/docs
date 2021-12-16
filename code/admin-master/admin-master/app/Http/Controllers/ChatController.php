<?php
/**
 * Created by PhpStorm.
 * User: brainwilliam
 * Date: 2020/12/21
 * Time: 下午4:55
 */

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\Resource;
use App\Pockets\EsPocket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class ChatController
 * @package App\Http\Controllers
 */
class ChatController extends BaseController
{
    /**
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function user(Request $request)
    {
        $reqGet        = request()->all();
        $sendNumber    = $reqGet['send_number'] ?? 0;
        $receiveNumber = $reqGet['receive_number'] ?? 0;
        $keyword       = $reqGet['keyword'] ?? 0;
        $startTime     = isset($reqGet['start_time']) ? intval(Carbon::createFromDate($reqGet['start_time'])->timestamp . '000') : 0;
        $endTime       = isset($reqGet['end_time']) ? intval(Carbon::createFromDate($reqGet['end_time'])->timestamp . '000') : 0;
        $sendId        = rep()->user->m()->where('uuid', $sendNumber)->value('id');
        $receiveId     = rep()->user->m()->where('uuid', $receiveNumber)->value('id');
        $filters       = $ranges = [];
        if ($startTime > 0) {
            $ranges[] = ['send_at' => ['from' => $startTime]];
        }
        if ($endTime > 0) {
            $ranges[] = ['send_at' => ['lte' => $endTime]];
        }
        $send = $receive = [];
        if (!$keyword && ((int)$sendNumber + (int)$receiveNumber) <= 0) {
            $response = pocket()->esImChat->getImChatGroupBy($filters, $ranges, 'send_id');
            if (!$response->getStatus()) {
                return api_rr()->notFoundResult("全部数据无结果");
            }
            $sendUserIds = $response->getData()['data'];
            $sendUsers   = pocket()->user->getUserInfoWithAvatar($sendUserIds);
            $send        = pocket()->userRole->getUserRoleStr($sendUsers);
            $receive     = [];
        }
        //仅通过关键词查询
        if ($keyword && $sendId == 0 && $receiveId == 0) {
            $filters[] = ['content' => ['query' => $keyword]];
            $response  = pocket()->esImChat->getImChatGroupBy($filters, $ranges, 'send_id');
            if (!$response->getStatus()) {
                return api_rr()->notFoundResult("关键词无结果");
            }
            $sendUserIds = $response->getData()['data'];
            $sendUsers   = pocket()->user->getUserInfoWithAvatar($sendUserIds);
            $send        = pocket()->userRole->getUserRoleStr($sendUsers);
            $receive     = [];
        }
        if ($sendId > 0 && $receiveId > 0) {
            $sendUsers    = pocket()->user->getUserInfoWithAvatar([$sendId]);
            $receiveUsers = pocket()->user->getUserInfoWithAvatar([$receiveId]);
            $send         = pocket()->userRole->getUserRoleStr($sendUsers);
            $receive      = pocket()->userRole->getUserRoleStr($receiveUsers);
        }
        //关键词+任何一方id
        if ($sendId > 0 && $receiveId == 0) {
            $filters[] = ['send_id' => ['query' => $sendId]];
            if ($keyword) {
                $filters[] = ['content' => ['query' => $keyword]];
            }
            $response = pocket()->esImChat->getImChatGroupBy($filters, $ranges, 'receive_id');
            if (!$response->getStatus()) {
                return api_rr()->notFoundResult("发送方无结果");
            }
            $receiveUseIds = $response->getData()['data'];

            $sendUsers    = pocket()->user->getUserInfoWithAvatar([$sendId]);
            $receiveUsers = pocket()->user->getUserInfoWithAvatar($receiveUseIds);
            $receive      = pocket()->userRole->getUserRoleStr($receiveUsers);
            $send         = pocket()->userRole->getUserRoleStr($sendUsers);
        }
        if ($sendId == 0 && $receiveId > 0) {
            $filters[] = ['receive_id' => ['query' => $receiveId]];
            if ($keyword) {
                $filters[] = ['content' => ['query' => $keyword]];
            }
            $response = pocket()->esImChat->getImChatGroupBy($filters, $ranges, 'send_id');
            if (!$response->getStatus()) {
                return api_rr()->notFoundResult("接收方无结果");
            }
            $sendUserIds  = $response->getData()['data'];
            $sendUsers    = pocket()->user->getUserInfoWithAvatar($sendUserIds);
            $receiveUsers = pocket()->user->getUserInfoWithAvatar([$receiveId]);
            $send         = pocket()->userRole->getUserRoleStr($sendUsers);
            $receive      = pocket()->userRole->getUserRoleStr($receiveUsers);
        }
        //        $spamUsers = mongodb('message_spam')->select('send_id')->groupBy('send_id')->get();
        //        $spamUUids = rep()->user->m()
        //            ->select(['uuid'])
        //            ->whereIn('id', $spamUsers->pluck('send_id')->toArray())
        //            ->get()->pluck('uuid')->toArray();
        //        foreach ($send as $item) {
        //            $item['is_spam'] = in_array($item->uuid, $spamUUids);
        //        }
        $data = [
            'send'    => $send,
            'receive' => $receive
        ];

        return api_rr()->getOK($data);
    }

    /**
     * 查看两个人的聊天记录
     *
     * @param  Request  $request
     * @param           $sendNumber
     * @param           $receiveNumber
     *
     * @return JsonResponse
     */
    public function show(Request $request, $sendNumber, $receiveNumber) : JsonResponse
    {
        $reqGet        = request()->all();
        $page          = $reqGet['page'] ?? 1;
        $limit         = $reqGet['limit'] ?? 1000;
        $keyword       = $reqGet['keyword'] ?? '';
        $exceptKeyword = $reqGet['except_keyword'] ?? 0;
        $startTime     = isset($reqGet['start_time']) ? intval(Carbon::createFromDate($reqGet['start_time'])->timestamp . '000') : 0;
        $endTime       = isset($reqGet['end_time']) ? intval(Carbon::createFromDate($reqGet['end_time'])->timestamp . '000') : 0;
        $fileds        = ['send_id', 'receive_id', 'content', 'send_at', 'type'];
        //保留方便后期改为number
        $sendId    = rep()->user->m()->where('uuid', $sendNumber)->value('id');
        $receiveId = rep()->user->m()->where('uuid', $receiveNumber)->value('id');
        if ($exceptKeyword) {
            $keyword = '';
        }
        if (!$sendId || !$receiveId) {
            return api_rr()->notFoundResult("用户不存在");
        }
        $response = pocket()->esImChat->searchImChat(
            $sendId, $receiveId, $startTime,
            $endTime, $keyword, $fileds, $limit, $page
        );
        if (!$response->getStatus()) {
            return api_rr()->notFoundResult("无结果");
        }
        $user       = pocket()->user->getUserInfoWithAvatar([$sendId, $receiveId]);
        $user       = pocket()->userRole->getUserRoleStr($user);
        $data       = [];
        $resultData = $response->getData();
        if ($resultData['data']) {
            $repData = [];
            foreach ($resultData['data'] as $key => $item) {
                if (isset($item['type']) && $item['type'] == EsPocket::TYPE_TEXT) {
                    $content = $item['content'];
                } else {
                    $content = cdn_url($item['content']);
                }
                $repData[] = [
                    'user_id'   => $item['send_id'],
                    'user_info' => $user->where('id', $item['send_id'])->first(),
                    'content'   => $content,
                    'send_at'   => $item['send_at'],
                    'type'      => $item['type'] ?? 0,
                    'event_at'  => Carbon::createFromTimestamp(intval(substr($item['send_at'], 0,
                        10)))->format('Y-m-d H:i:s'),
                    'position'  => $item['send_id'] == $sendId ? 'left' : 'right',
                ];
            }
            $data = [
                'list' => [
                    'current_page' => $resultData['current_page'],
                    'next_page'    => $resultData['next_page'],
                    'limit'        => $resultData['limit'],
                    'data'         => $repData
                ]
            ];
        }

        return api_rr()->getOK($data);
    }

    /**
     * 异常聊天列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function spamChat(Request $request)
    {
        $reqGet    = request()->all();
        $page      = (int)($reqGet['page'] ?? 1);
        $limit     = (int)($reqGet['limit'] ?? 10);
        $uuid      = (int)($reqGet['id'] ?? 0);
        $mobile    = (int)($reqGet['mobile'] ?? 0);
        $status    = (int)($reqGet['status'] ?? 0);
        $odd       = $reqGet['odd_even'] ?? 'all';
        $offset    = ($page - 1) * $limit;
        $startTime = key_exists('start_time',
            $reqGet) && $reqGet['start_time'] != '' ? strtotime($reqGet['start_time']) : 0;
        $endTime   = key_exists('end_time',
            $reqGet) && $reqGet['end_time'] != '' ? strtotime($reqGet['end_time']) : time();
        $roleMap   = [
            Role::KEY_USER       => '普通用户',
            Role::KEY_CHARM_GIRL => '魅力女生',
            Role::KEY_AUTH_USER  => '认证用户',
        ];

        $user = rep()->user->m()
            ->where('uuid', $uuid)
            ->orWhere(function ($query) use ($mobile) {
                $query->when($mobile > 0, function ($query) use ($mobile) {
                    $query->where('mobile', $mobile);
                });
            })
            ->first();

        $matchArr               = [];
        $matchArr['created_at'] = [
            '$gte' => $startTime,
            '$lt'  => $endTime
        ];
        $statusCode             = 100;
        if ($status == 0) {
            $matchArr['status'] = ['$ne' => $statusCode];
        } elseif ($statusCode == $status) {
            $matchArr['status'] = $statusCode;
        }
        if ($user) {
            $matchArr['send_id'] = $user->id;
        }
        $list = mongodb('message_spam')
            ->raw(function ($query) use ($offset, $limit, $matchArr) {

                return $query->aggregate([
                    [
                        '$match' => $matchArr,
                    ],
                    [
                        '$group' => [
                            '_id'        => '$send_id',
                            'send_count' => ['$sum' => 1]
                        ]
                    ],
                    [
                        '$match' => ['send_count' => ['$gte' => 10]],
                    ],
                    [
                        '$sort' => ['send_count' => -1]
                    ],
                    [
                        '$skip' => $offset
                    ],
                    [
                        '$limit' => $limit
                    ]
                ]);
            });
        $count    = mongodb('message_spam')
            ->raw(function ($query) use ($offset, $limit, $matchArr) {

                return $query->aggregate([
                    [
                        '$match' => $matchArr,
                    ],
                    [
                        '$group' => [
                            '_id'        => '$send_id',
                            'send_count' => ['$sum' => 1]
                        ],
                    ],
                    [
                        '$match' => ['send_count' => ['$gte' => 10]],
                    ],
                    ['$count' => 'count']
                ]);
            });
        $countArr = $count->toArray();
        if (count($countArr) == 0) {
            return api_rr()->forbidCommon('暂无数据');
        }
        $count      = $countArr[0]->jsonSerialize()->count;
        $data       = [];
        $k          = 0;
        $result     = [];
        $userArr    = [];
        $connection = collect($list->toArray());
        $users      = rep()->user->getUsersById(
            $connection->pluck('_id')->toArray(),
            ['id', 'uuid', 'nickname', 'role']
        );
        $oddArr     = $evenArr = [];
        foreach ($users as $user) {
            $chineseArr = [];
            $arr        = explode(',', $user->role);
            foreach ($arr as $item) {
                $chineseArr[] = $roleMap[$item];
            }
            $user->setAttribute('role', implode(',', $chineseArr));
            $userArr[$user->id] = $user;
            if (($user->id % 2) == 1) {
                $oddArr[$user->id] = $user;
            } else {
                $evenArr[$user->id] = $user;
            }
        }
        switch ($odd) {
            //奇数
            case 'odd':
                $userArr = $oddArr;
                break;
            //偶数
            case 'even':
                $userArr = $evenArr;
                break;
            case 'all':
                break;
        }
        foreach ($connection as $item) {
            if (isset($userArr[$item->jsonSerialize()->_id]) && $userArr[$item->jsonSerialize()->_id]) {
                $data[$k]['user']       = $userArr[$item->jsonSerialize()->_id] ?? 0;
                $data[$k]['send_count'] = $item->jsonSerialize()->send_count;
                $k++;
            }
        }
        $result['record'] = array_values($data);
        $result['limit']  = $limit;
        $result['count']  = $count;

        return api_rr()->getOK($result);
    }

    /**
     * 异常聊天详情
     *
     * @param $uuid
     *
     * @return JsonResponse
     */
    public function spamDetail($uuid)
    {
        $user = rep()->user->getByUUid($uuid, ['id', 'uuid', 'number', 'gender', 'nickname']);
        if (!$user) {
            return api_rr()->forbidCommon('未找到当前用户');
        }
        $avatar = rep()->resource->m()
            ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
            ->where('related_id', $user->id)
            ->first();
        if (!$avatar) {
            return api_rr()->notFoundResult('当前用户没有头像');
        }
        $user->setAttribute('avatar', cdn_url($avatar->resource));
        $details = mongodb('message_spam')->where('send_id', $user->id)->get();
        if (count($details) == 0) {
            return api_rr()->forbidCommon('未找到该用户聊天记录');
        }
        $result       = [
            'send_user' => $user,
            'receive'   => [],
        ];
        $receiveUsers = rep()->user->getUsersById(
            $details->pluck('receive_id')->toArray(),
            ['id', 'uuid', 'number', 'gender', 'nickname']
        );
        $avatars      = rep()->resource->m()
            ->whereIn('related_id', $receiveUsers->pluck('id')->toArray())
            ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
            ->get();
        $avatarArr    = [];
        foreach ($avatars as $avatar) {
            $avatarArr[$avatar->related_id] = $avatar->resource;
        }
        $userArr = [];
        foreach ($receiveUsers as $receiveUser) {
            $userArr[$receiveUser->id] = $receiveUser;
            $receiveUser->setAttribute('avatar',
                key_exists($receiveUser->id, $avatarArr) ? cdn_url($avatarArr[$receiveUser->id]) : '');
        }
        foreach ($details as $detail) {
            if ($detail['receive_id'] == 0) {
                continue;
            }
            $result['receive'][] = [
                'user'    => $userArr[$detail['receive_id']],
                'content' => $detail['content']
            ];
        }

        return api_rr()->getOK($result);
    }

    /**
     * 标记为已处理
     *
     * @param $id
     *
     * @return JsonResponse
     */
    public function spamMark($uuid)
    {
        $user = rep()->user->m()->where('uuid', $uuid)->first();
        mongodb('message_spam')->where('send_id', $user->id)->update(['status' => 100]);
        rep()->operatorSpecialLog->setNewLog($uuid, '异常聊天记录', '处理', '', $this->getAuthAdminId());

        return api_rr()->postOK([]);
    }
}

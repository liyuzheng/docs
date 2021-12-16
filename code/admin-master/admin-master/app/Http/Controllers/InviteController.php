<?php


namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MemberRecord;
use App\Models\Task;
use App\Models\MemberPunishment;
use App\Constant\NeteaseCustomCode;
use App\Models\AdminSendNetease;
use App\Models\User;
use App\Models\UserAb;

class InviteController extends BaseController
{
    /**
     * 疑似刷邀请列表
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInviteUsers(Request $request)
    {
        $limit        = $request->get('limit', 10);
        $page         = $request->get('page', 1);
        $startTime    = $request->get('start_time', '1970-01-01');
        $endTime      = $request->get('end_time', date('Y-m-d H:i:s', time()));
        $id           = $request->get('id');
        $mobile       = $request->get('mobile');
        $users        = rep()->user->m()
            ->select([
                'user.id',
                'user.uuid',
                'user.mobile',
                'user.nickname',
                DB::raw('count(distinct(invite_record.target_user_id)) as invite_count')
            ])
            ->join('invite_record', 'user.id', '=', 'invite_record.user_id')
            ->where('user.gender', User::GENDER_MAN)
            ->whereBetween('invite_record.done_at', [strtotime($startTime), strtotime($endTime)])
            ->when($id, function ($query) use ($id) {
                $query->where('user.uuid', $id);
            })
            ->when($mobile, function ($query) use ($mobile) {
                $query->where('user.mobile', $mobile);
            })
            ->groupBy('invite_record.user_id')
            ->orderByDesc('invite_count')
            ->orderByDesc('invite_record.user_id')
            ->having('invite_count', '>=', 5)
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();
        $userIds      = $users->pluck('id')->toArray();
        $tradePay     = rep()->tradePay->m()
            ->select(['user_id', DB::raw('sum(amount) as amount'), 'created_at'])
            ->whereIn('user_id', $userIds)
            ->where('done_at', '!=', 0)
            ->groupBy('user_id')
            ->get();
        $tradePayData = [];
        foreach ($tradePay as $item) {
            $tradePayData[$item->user_id] = $item->amount / 100;
        }
        $task          = rep()->task->m()
            ->whereIn('user_id', $userIds)
            ->get();
        $taskPrize     = rep()->taskPrize->m()
            ->whereIn('task_id', $task->pluck('id')->toArray())
            ->get();
        $taskPrizeData = [];
        foreach ($taskPrize as $item) {
            $taskPrizeData[$item->task_id] = $item->value;
        }
        $taskData = [];
        foreach ($task as $item) {
            if (!key_exists($item->user_id, $taskData)) {
                $taskData[$item->user_id] = [
                    'success' => 0,
                    'fail'    => 0
                ];
            }
            $value = $taskPrizeData[$item->id];
            if ($item->status == Task::STATUS_SUCCEED) {
                $taskData[$item->user_id]['success'] += $value;
            } else {
                $taskData[$item->user_id]['fail'] += $value;
            }
        }
        $memberRecord     = rep()->memberRecord->m()
            ->whereIn('type', [MemberRecord::TYPE_INVITE_USER, MemberRecord::TYPE_INVITE_USER_MEMBER])
            ->where('expired_at', '>', time())
            ->whereIn('user_id', $userIds)
            ->get();
        $memberRecordData = [];
        foreach ($memberRecord as $item) {
            if (!key_exists($item->user_id, $memberRecordData)) {
                $memberRecordData[$item->user_id] = 0;
            }
            if ($item->expired_at - $item->duration > time()) {
                $memberRecordData[$item->user_id] += $item->duration / 86400;
            } else {
                $memberRecordData[$item->user_id] += ($item->expired_at - time()) / 86400;
            }
        }
        $userMark     = mongodb('user_info')->whereIn('_id', $userIds)->get();
        $userMarkData = [];
        foreach ($userMark as $item) {
            $userMarkData[$item['_id']] = key_exists('mark', $item) ? $item['mark'] : 0;
        }
        foreach ($users as $user) {
            $user->setAttribute('amount', key_exists($user->id, $tradePayData) ? $tradePayData[$user->id] : 0);
            $user->setAttribute('received_member',
                key_exists($user->id, $taskData) ? intval(ceil($taskData[$user->id]['success'] / 86400)) : 0);
            $user->setAttribute('unreceived_member',
                key_exists($user->id, $taskData) ? intval(ceil($taskData[$user->id]['fail'] / 86400)) : 0);
            $user->setAttribute('remain_member',
                key_exists($user->id, $memberRecordData) ? intval(ceil($memberRecordData[$user->id])) : 0);
            $user->setAttribute('mark_count', key_exists($user->id, $userMarkData) ? $userMarkData[$user->id] : 0);
        }

        $allCount = rep()->user->m()
            ->select([
                'user.uuid',
                'user.mobile',
                'user.nickname',
                DB::raw('count(invite_record.target_user_id) as invite_count')
            ])
            ->join('invite_record', 'user.id', '=', 'invite_record.user_id')
            ->whereBetween('invite_record.done_at', [strtotime($startTime), strtotime($endTime)])
            ->where('user.gender', User::GENDER_MAN)
            ->when($id, function ($query) use ($id) {
                $query->where('user.uuid', $id);
            })
            ->when($mobile, function ($query) use ($mobile) {
                $query->where('user.mobile', $mobile);
            })
            ->groupBy('invite_record.user_id')
            ->having('invite_count', '>', 5)
            ->get();

        return api_rr()->getOK(['data' => $users, 'all_count' => count($allCount), 'limit' => $limit]);
    }

    /**
     * 获取邀请详情
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function inviteDetail(Request $request, $uuid)
    {
        $startTime = $request->get('start_time', '1970-01-01');
        $endTime   = $request->get('end_time', date('Y-m-d H:i:s', time()));
        $limit     = $request->get('limit', 10);
        $page      = $request->get('page', 1);
        $user      = rep()->user->getByUUid($uuid);
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        $invites     = rep()->inviteRecord->m()
            ->where('user_id', $user->id)
            ->whereBetween('done_at', [strtotime($startTime), strtotime($endTime)])
            ->get()->pluck('target_user_id')->toArray();
        $inviteUsers = rep()->user->m()
            ->select(['id', 'uuid', 'nickname', 'mobile', 'gender', 'created_at'])
            ->whereIn('id', $invites)
            ->with([
                'userDetail' => function ($query) {
                    $query->select(['user_id', 'lat', 'lng']);
                }
            ])
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();
        pocket()->user->appendAvatarToUsers($inviteUsers);
        foreach ($inviteUsers as $inviteUser) {
            $inviteUser->setAttribute('create_time', (string)$inviteUser->created_at);
        }

        $count = rep()->user->m()
            ->whereIn('id', $invites)
            ->count();

        return api_rr()->getOK(['data' => $inviteUsers, 'all_count' => $count, 'limit' => $limit]);
    }

    /**
     * 惩罚扣除用户领取uip时间
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function punishment(Request $request, $uuid)
    {
        $operator   = $this->getAuthAdminId();
        $count      = $request->post('count');
        $content    = $request->post('content');
        $punishTime = $count * 86400;
        $now        = time();
        $user       = rep()->user->getByUUid($uuid);
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        $deleteMemberRecord = [];
        $deleteTask         = [];
        $task               = rep()->task->m()
            ->where('user_id', $user->id)
            ->where('status', Task::STATUS_DEFAULT)
            ->where('done_at', 0)
            ->get();
        $taskPrize          = rep()->taskPrize->m()
            ->whereIn('task_id', $task->pluck('id')->toArray())
            ->where('value', '>', 0)
            ->get();
        foreach ($taskPrize as $item) {
            if ($punishTime > $item->value) {
                $deleteTask[$item->task_id] = $item->value;
                $punishTime                 -= $item->value;
            } else {
                $deleteTask[$item->task_id] = $punishTime;
                $punishTime                 -= $punishTime;
            }
        }
        if ($punishTime > 0) {
            $memberRecord = rep()->memberRecord->m()
                ->where('user_id', $user->id)
                ->whereIn('type', [MemberRecord::TYPE_INVITE_USER, MemberRecord::TYPE_INVITE_USER_MEMBER])
                ->where('expired_at', '>', $now)
                ->get();
            foreach ($memberRecord as $item) {
                $remainTime = ($item->expired_at - $now);
                $delTime    = $remainTime > $item->duration ? $item->duration : $remainTime;
                if ($punishTime > $delTime) {
                    $deleteMemberRecord[$item->id] = $delTime;
                    $punishTime                    -= $delTime;
                } else {
                    $deleteMemberRecord[$item->id] = $punishTime;
                    $punishTime                    -= $punishTime;
                }
            }
        }

        if ($punishTime >= 86400) {
            return api_rr()->forbidCommon('当前用户剩余天数不足，请重新输入');
        }

        DB::transaction(function () use ($deleteMemberRecord, $deleteTask, $operator, $user, $now, $punishTime) {
            $createPunishment  = [];
            $needDecrementTime = 0;
            foreach ($deleteMemberRecord as $key => $value) {
                $createPunishment[] = [
                    'user_id'    => $user->id,
                    'type'       => MemberPunishment::TYPE_MEMBER,
                    'value'      => $value,
                    'operator'   => $operator,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $needDecrementTime  += $value;
                rep()->memberRecord->m()->where('id', $key)->update([
                    'duration'   => DB::raw('duration - ' . $value),
                    'expired_at' => DB::raw('expired_at - ' . $needDecrementTime)
                ]);
            }
            foreach ($deleteTask as $key => $value) {
                $createPunishment[] = [
                    'user_id'    => $user->id,
                    'type'       => MemberPunishment::TYPE_TASK,
                    'value'      => $value,
                    'operator'   => $operator,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                rep()->taskPrize->m()->where('task_id', $key)->decrement('value', $value);
            }

            rep()->wallet->m()->where('user_id', $user->id)->decrement('free_vip', array_sum($deleteTask));
            rep()->member->m()->where('user_id', $user->id)->decrement('duration', array_sum($deleteMemberRecord));
            rep()->memberPunishment->m()->insert($createPunishment);
            $redisKeys = sprintf(config('redis_keys.lock_mark_user'), $user->id);
            if (!redis()->client()->get($redisKeys)) {
                $userMongo = mongodb('user_info')->where('_id', $user->id);
                $mongo     = $userMongo->first();
                if ($mongo && key_exists('mark', $mongo) && $mongo['mark'] == 1) {
                    $offset = 86400 * 3;
                } else {
                    $offset = 86400;
                }
                $refreshMark = time() + $offset;
                if ($mongo) {
                    $userMongo->increment('mark');
                    $userMongo->update(['expired_at' => $refreshMark]);
                } else {
                    $userMongo->insert(['_id' => $user->id, 'mark' => 1, 'refresh_mark' => time() + $offset]);
                }
                redis()->client()->set($redisKeys, true);
                redis()->client()->expire($redisKeys, $offset);
            }
        });
        $extension  = [
            'option' => [
                'badge' => false
            ]
        ];
        $data       = [
            'type' => NeteaseCustomCode::STRONG_REMINDER,
            'data' => [
                'title'   => '警告',
                'content' => $content
            ]
        ];
        $createData = [
            'type'      => AdminSendNetease::TYPE_STRONG_REMIND,
            'msg'       => json_encode($data),
            'target_id' => $user->id,
            'operator'  => $operator
        ];
        pocket()->common->sendNimMsgQueueMoreByPocketJob(
            pocket()->netease,
            'msgSendCustomMsg',
            [config('custom.little_helper_uuid'), $uuid, $data, $extension]
        );
        rep()->adminSendNetease->m()->create($createData);
        pocket()->common->sendNimMsgQueueMoreByPocketJob(
            pocket()->netease,
            'msgSendMsg',
            [config('custom.little_helper_uuid'), $uuid, $content]
        );
        $userMongo = mongodb('user_info')->where('_id', $user->id)->first();
        if ($userMongo['mark'] >= 3) {
            $mark = '永久';
        } else {
            $mark = MemberPunishment::MESSAGE_MAPPING[$userMongo['mark']];
        }
        $message = '平台将取消你邀请功能' . $mark . '处罚，请勿使用第三方手段进行无效邀请。';
        pocket()->common->sendNimMsgQueueMoreByPocketJob(
            pocket()->netease,
            'msgSendMsg',
            [config('custom.little_helper_uuid'), $uuid, $message]
        );

        rep()->operatorSpecialLog->setNewLog($uuid, '取消VIP', '扣除' . $count . '天', $content, $this->getAuthAdminId());

        return api_rr()->postOK([]);
    }

    /**
     * 操作记录列表
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function punishList(Request $request)
    {
        $id        = $request->get('uuid');
        $limit     = $request->get('limit', 10);
        $page      = $request->get('page', 1);
        $startTime = $request->get('start_time', '1970-01-01');
        $endTime   = $request->get('end_time', date('Y-m-d H:i:s', time()));

        $listQuery = rep()->memberPunishment->m()->join('user', 'user.id', '=', 'member_punishment.user_id')
            ->groupBy(['user_id', 'member_punishment.created_at'])->when($id, function ($query) use ($id) {
                $query->where('user.uuid', $id);
            })->whereBetween('member_punishment.created_at',
                [strtotime($startTime), strtotime($endTime)]);

        $list = (clone $listQuery)->select([
            'member_punishment.user_id',
            'user.uuid',
            'user.nickname',
            DB::raw('sum(member_punishment.value) / 86400 as value'),
            'member_punishment.operator',
            'member_punishment.created_at'
        ])->with(['operator'])
            ->orderByDesc('member_punishment.created_at')
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();

        foreach ($list as $item) {
            $item->setAttribute('create_time', (string)$item->created_at);
            $item->setAttribute('value', intval(ceil($item->value)));
            $item->setAttribute('uuid', (string)$item->uuid);
        }

        $count = rep()->user->getQuery()->fromSub($listQuery->select('user_id'), 'res')
            ->withTrashed()->count();

        return api_rr()->getOK(['data' => $list, 'all_count' => $count, 'limit' => $limit]);
    }
}

<?php


namespace App\Http\Controllers;

use App\Models\InviteRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Models\UserReview;
use App\Models\Report;
use App\Models\UserRelation;
use App\Models\Tag;
use App\Models\Wechat;
use App\Models\Resource;
use App\Models\UserResource;
use App\Http\Requests\Admin\BlacklistRequest;
use App\Models\Blacklist;
use App\Http\Requests\Admin\BlackDelRequest;
use App\Models\User;
use App\Models\Member;
use App\Models\UserContact;
use App\Models\TradeWithdraw;
use App\Jobs\WgcJob;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\ReportFeedback;
use App\Models\SfaceRecord;
use App\Constant\NeteaseCustomCode;
use App\Models\AdminSendNetease;
use App\Models\Repay;
use App\Models\SwitchModel;
use App\Models\UserSwitch;
use App\Models\Role;
use App\Models\LoginFaceRecord;
use Illuminate\Auth\Events\Login;
use App\Models\UserLike;

class UserController extends BaseController
{
    /**
     * 用户详情
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return JsonResponse
     */
    public function detail(Request $request, $uuid)
    {
        $user    = pocket()->user->adminGetUser($uuid);
        $facePic = rep()->facePic->m()->where('user_id', $user->id)->orderByDesc('id')->first();
        $user->setAttribute('face_pic', $facePic ? [cdn_url($facePic->base_map)] : []);
        $loginRecord = rep()->loginFaceRecord->m()
            ->select(['face_pic', 'login_status', 'created_at'])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();
        if ($loginRecord) {
            $data = [
                'face_pic'     => $loginRecord->face_pic ? cdn_url($loginRecord->face_pic) : '',
                'login_status' => $loginRecord->login_status == LoginFaceRecord::LOGIN_STATUS_SUCCESS ? '成功' : '失败',
                'create_time'  => (string)$loginRecord->created_at,
            ];
            $user->setAttribute('login_record', $data);
        } else {
            $user->setAttribute('login_record', [
                'face_pic'     => '',
                'login_status' => '',
                'create_time'  => ''
            ]);
        }

        return api_rr()->getOK($user);
    }

    /**
     * 用户列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function list(Request $request)
    {
        $id         = $request->get('id');
        $nickname   = $request->get('nickname');
        $mobile     = $request->get('mobile');
        $now        = time();
        $limit      = $request->get('limit', 10);
        $page       = $request->get('page', 1);
        $list       = rep()->user->m()
            ->select('user.id', 'user.uuid', 'user.nickname', 'user.mobile',
                'user.created_at', 'user.gender', 'user.role', 'user.hide',
                'user_detail.channel', 'user_detail.os', 'user_detail.channel')
            ->join('user_detail', 'user.id', '=', 'user_detail.user_id')
            ->when($id, function ($query) use ($id) {
                $query->where('user.uuid', $id);
            })->when($nickname, function ($query) use ($nickname) {
                $query->where('user.nickname', $nickname);
            })->when($mobile, function ($query) use ($mobile) {
                $query->where('user.mobile', $mobile);
            })
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('user.id')
            ->get();
        $members    = rep()->member->m()
            ->whereIn('user_id', $list->pluck('id')->toArray())
            ->where(DB::raw('start_at + duration'), '>', $now)
            ->get();
        $memberList = [];
        foreach ($members as $member) {
            $memberList[] = $member->user_id;
        }
        $switch         = rep()->switchModel->getQuery()->where('key',
            SwitchModel::KEY_CLOSE_WE_CHAT_TRADE)->first();
        $userSwitches   = rep()->userSwitch->getQuery()->select('user_id', 'status')
            ->whereIn('user_id', $list->pluck('id')->toArray())
            ->where('switch_id', $switch->id)->get();
        $userSwitchData = [];
        foreach ($userSwitches as $userSwitch) {
            $userSwitchData[$userSwitch->user_id] = (bool)$userSwitch->status;
        }

        foreach ($list as $user) {
            $user->setAttribute('is_close_we_chat_trade', $userSwitchData[$user->id] ?? false);
            $user->setAttribute('create_time', (string)$user->created_at);
            $user->setAttribute('is_member', (bool)in_array($user->id, $memberList));
            $user->setAttribute('user_role', implode(',', array_map(function ($key) {
                return User::ROLE_ARR[$key];
            }, explode(',', $user->role))));
            $user->setAttribute('mobile', substr_replace($user->mobile, '****', 3, 4));
        }
        $allCount = rep()->user->m()
            ->when($id, function ($query) use ($id) {
                $query->where('number', $id);
            })->when($nickname, function ($query) use ($nickname) {
                $query->where('nickname', $nickname);
            })->when($mobile, function ($query) use ($mobile) {
                $query->where('mobile', $mobile);
            })->count();

        return api_rr()->getOK(['data' => $list->toArray(), 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 魅力女生列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function charmList(Request $request)
    {
        $id        = $request->get('id');
        $nickname  = $request->get('nickname');
        $mobile    = $request->get('mobile');
        $limit     = $request->get('limit', 10);
        $page      = $request->get('page', 1);
        $isMark    = $request->get('is_mark', false);
        $region    = $request->get('region');
        $startTime = $request->post('start_time', '1970-01-01');
        $endTime   = $request->post('end_time', date('Y-m-d H:i:s', time()));
        $roles     = pocket()->role->getUserRoleArr(['charm_girl']);
        $markUser  = mongodb('mark_charm_girl')->get()->pluck('user_id')->toArray();
        $list      = rep()->user->m()
            ->select([
                'user.id',
                'user.uuid',
                'user.number',
                'user.mobile',
                'user.nickname',
                'user.created_at',
                'user.gender',
                'user.role',
                'user.hide',
                'user.active_at',
                'user_detail.region'
            ])
            ->join('user_detail', 'user_detail.user_id', '=', 'user.id')
            ->whereIn('user.role', $roles)
            ->when($region, function ($query) use ($region) {
                $query->where('user_detail.region', $region);
            })
            ->whereBetween('user.active_at', [strtotime($startTime), strtotime($endTime)])
            ->when($id, function ($query) use ($id) {
                $query->where('user.uuid', $id);
            })
            ->when($nickname, function ($query) use ($nickname) {
                $query->where('user.nickname', $nickname);
            })
            ->when($mobile, function ($query) use ($mobile) {
                $query->where('user.mobile', $mobile);
            })
            ->when($isMark === 'true', function ($query) use ($markUser) {
                $query->whereIn('user.id', $markUser);
            })
            ->when($isMark === 'false', function ($query) use ($markUser) {
                $query->whereNotIn('user.id', $markUser);
            })
            ->with([
                'userReview' => function ($query) {
                    $query->select(['user_id', 'created_at'])->where('check_status', UserReview::CHECK_STATUS_PASS);
                }
            ])
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('user.id')
            ->get();

        $likeUsers  = [];
        $likeCounts = rep()->userFollow->m()
            ->select(['follow_id', DB::raw('count(*) as cnt')])
            ->whereIn('follow_id', $list->pluck('id')->toArray())
            ->groupBy('follow_id')
            ->get();
        foreach ($likeCounts as $likeCount) {
            $likeUsers[$likeCount->follow_id] = $likeCount->cnt;
        }

        $unlockWechats = rep()->userRelation->m()
            ->select(['target_user_id', DB::raw('count(*) as count')])
            ->where('type', UserRelation::TYPE_LOOK_WECHAT)
            ->whereIn('target_user_id', $list->pluck('id')->toArray())
            ->groupBy('target_user_id')
            ->get();

        $switch     = rep()->switchModel->m()->where('key', SwitchModel::KEY_LOCK_WECHAT)->first();
        $userSwitch = rep()->userSwitch->m()
            ->where('switch_id', $switch->id)
            ->whereIn('status', [UserSwitch::STATUS_OPEN, UserSwitch::STATUS_ADMIN_LOCK])
            ->whereIn('user_id', $list->pluck('id')->toArray())
            ->get();
        $switches   = [];
        foreach ($userSwitch as $item) {
            $switches[] = $item->user_id;
        }

        $wechats = [];
        foreach ($unlockWechats as $unlockWechat) {
            $wechats[$unlockWechat->target_user_id] = $unlockWechat->count;
        }

        $charmCount = rep()->user->m()
            ->where('role', 'auth_user,charm_girl,user')
            ->count();
        $regionsHas = array_unique($list->pluck('region')->toArray());
        $regionsGroup = rep()->user->m()
            ->select(['user_detail.region', DB::raw('count(user.id) as total')])
            ->join('user_detail', 'user_detail.user_id', '=', 'user.id')
            ->whereIn('user_detail.region', $regionsHas)
            ->groupBy('user_detail.region')
            ->get();
        foreach ($regionsGroup as $key=>$value){
            $regionTotal[$value['region']] = $value['total'];
        }
        foreach ($list as $user) {
            if ($user->userReview) {
                $user->userReview->setAttribute('create_time', (string)$user->userReview->created_at);
            }
            $user->setAttribute('unlock_times', key_exists($user->id, $wechats) ? $wechats[$user->id] : 0);
            $user->setAttribute('like_times', key_exists($user->id, $likeUsers) ? $likeUsers[$user->id] : 0);
            $user->setAttribute('create_time', (string)$user->created_at);
            $user->setAttribute('is_lock_wechat', (bool)in_array($user->id, $switches));
            $user->setAttribute('is_mark', in_array($user->id, $markUser));
            $user->setAttribute('mobile', substr_replace($user->mobile, '****', 3, 4));
            $activeTime = time() - $user->active_at;
            if ($activeTime < (60 * 10)) {
                $active = '正在活跃';
            } elseif ($activeTime > 600 && $activeTime < 3600) {
                $active = intval(($activeTime / 60)) . '分钟前';
            } elseif ($activeTime > 3600 && $activeTime < 86400) {
                $active = intval(($activeTime / 3600)) . '小时前';
            } else {
                $active = intval(($activeTime / 86400)) . '天前';
            }
            $user->setAttribute('active', $active);
            $user->setAttribute('charm_count', $charmCount);
            $user->setAttribute('region_total',$regionTotal[$user->region]);
        }

        $allCount = rep()->user->m()
            ->join('user_detail', 'user_detail.user_id', '=', 'user.id')
            ->whereIn('user.role', $roles)
            ->when($region, function ($query) use ($region) {
                $query->where('user_detail.region', $region);
            })
            ->whereBetween('user.active_at', [strtotime($startTime), strtotime($endTime)])
            ->when($id, function ($query) use ($id) {
                $query->where('user.uuid', $id);
            })
            ->when($nickname, function ($query) use ($nickname) {
                $query->where('user.nickname', $nickname);
            })
            ->when($mobile, function ($query) use ($mobile) {
                $query->where('user.mobile', $mobile);
            })
            ->when($isMark === 'true', function ($query) use ($markUser) {
                $query->whereIn('user.id', $markUser);
            })
            ->when($isMark === 'false', function ($query) use ($markUser) {
                $query->whereNotIn('user.id', $markUser);
            })
            ->count();

        return api_rr()->getOK(['data' => $list->toArray(), 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 获取用户地区
     *
     * @return JsonResponse
     */
    public function getUserCitys()
    {
        $list = rep()->userDetail->m()
            ->select(['region'])
            ->groupBy('region')
            ->get();

        return api_rr()->getOK($list);
    }

    /**
     * 会员列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function memberList(Request $request)
    {
        $id       = $request->get('id');
        $nickname = $request->get('nickname');
        $mobile   = $request->get('mobile');
        $now      = time();
        $limit    = $request->get('limit', 10);
        $page     = $request->get('page', 1);

        $members = rep()->member->m()
            ->select(['user.uuid', 'member.user_id', 'member.start_at', 'member.duration'])
            ->join('user', 'member.user_id', '=', 'user.id')
            ->where(DB::raw('member.start_at + member.duration'), '>', $now)
            ->when($id, function ($query) use ($id) {
                $query->where('user.uuid', $id);
            })
            ->when($mobile, function ($query) use ($mobile) {
                $query->where('user.mobile', $mobile);
            })
            ->when($nickname, function ($query) use ($nickname) {
                $query->where('user.nickname', $nickname);
            })
            ->with([
                'user' => function ($query) use ($id, $nickname, $mobile) {
                    $query->select(['id', 'uuid', 'number', 'nickname', 'mobile', 'gender', 'created_at']);
                }
            ])
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('member.user_id')
            ->get();
        foreach ($members as $member) {
            $member->setAttribute('uuid', (string)$member->uuid);
            $member->user->setAttribute('create_time', (string)$member->user->created_at);
            $member->setAttribute('member_days', intval((time() - $member->start_at) / 86400));
            $member->setAttribute('delay_days', ceil(($member->start_at + $member->duration - time()) / 86400));
            $member->setHidden(array_diff($member->getHidden(), ['user_id']));
        }

        $allCount = rep()->member->m()
            ->join('user', 'member.user_id', '=', 'user.id')
            ->where(DB::raw('member.start_at + member.duration'), '>', $now)
            ->when($id, function ($query) use ($id) {
                $query->where('user.uuid', $id);
            })
            ->when($mobile, function ($query) use ($mobile) {
                $query->where('user.mobile', $mobile);
            })
            ->when($nickname, function ($query) use ($nickname) {
                $query->where('user.nickname', $nickname);
            })
            ->count();

        return api_rr()->getOK(['data' => $members->toArray(), 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 举报列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function export(Request $request)
    {
        $limit          = $request->get('limit', 10);
        $page           = $request->get('page', 1);
        $status         = $request->get('status', 'delay');
        $reportUUid     = $request->get('report_id');
        $reportedUUid   = $request->get('reported_id');
        $reportMobile   = $request->get('report_mobile');
        $reportedMobile = $request->get('reported_mobile');
        $startTime      = $request->post('start_time', '1970-01-01');
        $endTime        = $request->post('end_time', date('Y-m-d H:i:s', time()));
        $reportList     = rep()->report->m()
            ->select([
                'report.uuid',
                'report.reason',
                'report.remark',
                'report.user_id',
                'report.related_id',
                'report.created_at'
            ])
            ->where('report.related_type', Report::RELATED_TYPE_USER)
            ->join('user as ru', 'ru.id', '=', 'report.user_id')
            ->join('user as rdu', 'rdu.id', '=', 'report.related_id')
            ->whereBetween('report.created_at', [strtotime($startTime), strtotime($endTime)])
            ->when($reportUUid, function ($query) use ($reportUUid) {
                $query->where('ru.uuid', $reportUUid);
            })
            ->when($reportedUUid, function ($query) use ($reportedUUid) {
                $query->where('rdu.uuid', $reportedUUid);
            })
            ->when($reportMobile, function ($query) use ($reportMobile) {
                $query->where('ru.mobile', $reportMobile);
            })
            ->when($reportedMobile, function ($query) use ($reportedMobile) {
                $query->where('rdu.mobile', $reportedMobile);
            })
            ->with([
                'reportUser',
                'reportedUser'
            ])
            ->when($status == 'delay', function ($query) {
                $query->where('report.status', 0);
            }, function ($query) {
                $query->where('report.status', 100);
            })
            ->when($limit != -1, function ($query) use ($limit, $page) {
                $query->limit($limit)->offset(($page - 1) * $limit);
            })
            ->orderByDesc('report.updated_at')
            ->get();
        $reportPic      = rep()->resource->m()
            ->where('related_type', Resource::RELATED_TYPE_REPORT)
            ->whereIn('related_id', $reportList->pluck('id')->toArray())
            ->get();
        $reports        = [];
        foreach ($reportPic as $pic) {
            $reports[$pic->related_id][] = $pic;
        }
        foreach ($reportList as $report) {
            if ($status != 'delay') {
                $report->setAttribute('handle_time', (string)$report->updated_at);
            }
            $report->setAttribute('create_time', (string)$report->created_at);
            $report->setAttribute('pics', key_exists($report->id, $reports) ? $reports[$report->id] : []);
            $report->reportUser->mobile   = substr_replace($report->reportUser->mobile, '****', 3, 4);
            $report->reportedUser->mobile = substr_replace($report->reportedUser->mobile, '****', 3, 4);
        }
        $allCount = rep()->report->m()
            ->where('report.related_type', Report::RELATED_TYPE_USER)
            ->join('user as ru', 'ru.id', '=', 'report.user_id')
            ->join('user as rdu', 'rdu.id', '=', 'report.related_id')
            ->whereBetween('report.created_at', [strtotime($startTime), strtotime($endTime)])
            ->when($reportUUid, function ($query) use ($reportUUid) {
                $query->where('ru.uuid', $reportUUid);
            })
            ->when($reportedUUid, function ($query) use ($reportedUUid) {
                $query->where('rdu.uuid', $reportedUUid);
            })
            ->when($reportMobile, function ($query) use ($reportMobile) {
                $query->where('ru.mobile', $reportMobile);
            })
            ->when($reportedMobile, function ($query) use ($reportedMobile) {
                $query->where('rdu.mobile', $reportedMobile);
            })
            ->when($status == 'delay', function ($query) {
                $query->where('report.status', 0);
            }, function ($query) {
                $query->where('report.status', 100);
            })
            ->count();

        return api_rr()->getOK(['data' => $reportList->toArray(), 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 获取举报详情
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function reportDetail(Request $request, $uuid)
    {
        $detail       = rep()->report->m()->where('uuid', $uuid)->first();
        $reportUser   = rep()->user->getById($detail->user_id);
        $reportedUser = rep()->user->getById($detail->related_id);
        $detail->setAttribute('report_user', pocket()->user->adminGetUser($reportUser->uuid));
        $detail->setAttribute('reported_user', pocket()->user->adminGetUser($reportedUser->uuid));
        $resource = rep()->resource->m()
            ->where('related_type', Resource::RELATED_TYPE_REPORT)
            ->where('related_id', $detail->id)
            ->get()
            ->toArray();
        $detail->setAttribute('photo', $resource);
        $chatRecord = rep()->reportFeedback->m()
            ->where('report_id', $detail->id)
            ->get();
        $chat       = [];
        foreach ($chatRecord as $item) {
            if ($item->related_type == ReportFeedback::RELATED_TYPE_REPORT) {
                $chat['report'][] = [
                    'content' => $item->content,
                    'time'    => (string)$item->created_at
                ];
            } elseif ($item->related_type == ReportFeedback::RELATED_TYPE_REPORTED) {
                $chat['reported'][] = [
                    'content' => $item->content,
                    'time'    => (string)$item->created_at
                ];
            }
        }
        $detail->setAttribute('chat', $chat);

        return api_rr()->getOK($detail);
    }

    /**
     * 反馈列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function feedback(Request $request)
    {
        $limit        = $request->get('limit', 10);
        $page         = $request->get('page', 1);
        $uuid         = $request->get('id');
        $nickname     = $request->get('nickname');
        $status       = $request->get('status', 'delay');
        $feedbackList = rep()->report->m()
            ->select([
                'report.id',
                'report.uuid',
                'report.related_id',
                'report.user_id',
                'report.reason',
                'report.status',
                'report.created_at'
            ])
            ->join('user', 'user.id', '=', 'report.related_id')
            ->where('report.related_type', Report::RELATED_TYPE_APP)
            ->when($uuid, function ($query) use ($uuid) {
                $query->where('user.uuid', $uuid);
            })
            ->when($nickname, function ($query) use ($nickname) {
                $query->where('user.nickname', $nickname);
            })
            ->when($status == 'delay', function ($query) {
                $query->where('status', 0);
            }, function ($query) {
                $query->where('status', 100);
            })
            ->with([
                'reportUser',
                'reportedUser'
            ])
            ->when($limit != -1, function ($query) use ($limit, $page) {
                $query->limit($limit)->offset(($page - 1) * $limit);
            })
            ->orderByDesc('report.id')
            ->get();
        $feedbackPic  = rep()->resource->m()
            ->where('related_type', Resource::RELATED_TYPE_FEEDBACK)
            ->whereIn('related_id', $feedbackList->pluck('id')->toArray())
            ->get();
        $feedbackMsgs = rep()->reportFeedback->m()->whereIn('report_id', $feedbackList->pluck('id')->toArray())->get();
        $feedbacks    = [];
        foreach ($feedbackPic as $pic) {
            $feedbacks[$pic->related_id][] = $pic;
        }
        $feedbackMsg = [];
        foreach ($feedbackMsgs as $item) {
            $feedbackMsg[$item->report_id] = $item->content;
        }
        foreach ($feedbackList as $feedback) {
            $feedback->setAttribute('create_time', (string)$feedback->created_at);
            $feedback->setAttribute('pics', key_exists($feedback->id, $feedbacks) ? $feedbacks[$feedback->id] : []);
            $feedback->setAttribute('message', key_exists($feedback->id, $feedbackMsg) ? $feedbackMsg[$feedback->id] : '');
            $feedback->reportUser->mobile   = substr_replace($feedback->reportUser->mobile, '****', 3, 4);
            $feedback->reportedUser->mobile = substr_replace($feedback->reportedUser->mobile, '****', 3, 4);
        }
        $allCount = rep()->report->m()
            ->join('user', 'user.id', '=', 'report.related_id')
            ->where('report.related_type', Report::RELATED_TYPE_APP)
            ->when($uuid, function ($query) use ($uuid) {
                $query->where('user.uuid', $uuid);
            })
            ->when($nickname, function ($query) use ($nickname) {
                $query->where('user.nickname', $nickname);
            })
            ->when($status == 'delay', function ($query) {
                $query->where('status', 0);
            }, function ($query) {
                $query->where('status', 100);
            })
            ->count();

        return api_rr()->getOK(['data' => $feedbackList->toArray(), 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 提现列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function withdraw(Request $request)
    {
        $limit        = $request->get('limit', 10);
        $page         = $request->get('page', 1);
        $status       = $request->get('status', 0);
        $startTime    = $request->has('start_time') ? strtotime($request->get('start_time')) : 0;
        $endTime      = $request->has('start_time') ? strtotime($request->get('end_time')) : time();
        $platform     = $request->get('platform');
        $nickname     = $request->get('nickname');
        $realname     = $request->get('name');
        $uuid         = $request->get('id');
        $repayStatus  = $request->get('repay_status', '-1');
        $withdrawList = rep()->tradeWithdraw->m()
            ->select([
                'trade_withdraw.id',
                'trade_withdraw.repay_status',
                'user.uuid',
                'user.nickname',
                'trade_withdraw.type',
                'user_contact.name',
                'user_contact.account',
                'user_contact.region',
                'user_contact.platform',
                'user_contact.region_path',
                'ori_amount',
                DB::raw('(trade_withdraw.ori_amount - trade_withdraw.amount) / 100 as service_charge'),
                DB::raw('trade_withdraw.amount / 100 as amount'),
                'trade_withdraw.created_at',
                'trade_withdraw.done_at'
            ])
            ->join('user', 'trade_withdraw.user_id', '=', 'user.id')
            ->join('user_contact', 'trade_withdraw.contact_id', '=', 'user_contact.id')
            ->when($nickname, function ($query) use ($nickname) {
                $query->where('user.nickname', $nickname);
            })
            ->when($repayStatus != '-1', function ($query) use ($repayStatus) {
                $query->where('trade_withdraw.repay_status', $repayStatus);
            })
            ->when($uuid, function ($query) use ($uuid) {
                $query->where('user.uuid', $uuid);
            })
            ->when($status, function ($query) {
                $query->where('done_at', '>', 0)->orderByDesc('trade_withdraw.done_at');
            }, function ($query) {
                $query->where('done_at', 0)->orderByDesc('trade_withdraw.id');
            })
            ->when($platform, function ($query) use ($platform) {
                $query->where('user_contact.platform', $platform);
            })
            ->when($realname, function ($query) use ($realname) {
                $query->where('user_contact.name', $realname);
            })
            ->whereBetween('trade_withdraw.created_at', [$startTime, $endTime])
            ->when($page >= 0, function ($query) use ($limit, $page) {
                $query->limit($limit)->offset(($page - 1) * $limit);
            })->get();
        $regionArr    = [];
        $regionResult = [];
        foreach ($withdrawList as $item) {
            $region = explode('_', $item->region_path);
            foreach ($region as $key) {
                $regionArr[] = $key;
            }
        }
        $regionList = rep()->region->m()->whereIn('id', $regionArr)->get();
        foreach ($regionList as $item) {
            $regionResult[$item->id] = $item->name;
        }

        foreach ($withdrawList as $item) {
            $item->setAttribute('uuid', (string)$item->uuid);
            $item->setAttribute('withdraw_type', TradeWithdraw::TYPE_MAPPING[$item->type]);
            $item->setAttribute('platform', UserContact::PLATFORM_CHINESE_MAPPING[$item->platform]);
            $item->setAttribute('done_at', $item->done_at != 0 ? date('Y-m-d H:i:s', $item->done_at) : 0);
            $item->setAttribute('create_time', (string)$item->created_at);
            $region = explode('_', $item->region_path);
            if (key_exists($region[0], $regionResult) && key_exists($region[1], $regionResult)) {
                $regionStr = $regionResult[$region[0]] . '_' . $regionResult[$region[1]];
                $item->setAttribute('region_path', $regionStr);
            }
            $item->setAttribute('repay_status', $item->repay_status);
        }

        $withdrawCount = rep()->tradeWithdraw->m()
            ->join('user', 'trade_withdraw.user_id', '=', 'user.id')
            ->join('user_contact', 'trade_withdraw.contact_id', '=', 'user_contact.id')
            ->when($nickname, function ($query) use ($nickname) {
                $query->where('user.nickname', $nickname);
            })
            ->when($uuid, function ($query) use ($uuid) {
                $query->where('user.uuid', $uuid);
            })
            ->when($repayStatus != '-1', function ($query) use ($repayStatus) {
                $query->where('trade_withdraw.repay_status', $repayStatus);
            })
            ->when($status, function ($query) {
                $query->where('done_at', '>', 0);
            }, function ($query) {
                $query->where('done_at', 0);
            })
            ->whereBetween('trade_withdraw.created_at', [$startTime, $endTime])
            ->count();

        return api_rr()->getOK(['data' => $withdrawList->toArray(), 'all_count' => $withdrawCount, 'limit' => $limit]);
    }

    /**
     * 提现成功
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function commitWithdraw(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return api_rr()->notFoundResult('缺少提现ID');
        }
        $tradeWithdraw = rep()->tradeWithdraw->getById($id);
        if (!$tradeWithdraw) {
            return api_rr()->notFoundResult('无提现记录请重试');
        }
        $user = rep()->user->getById($tradeWithdraw->user_id);
        if (!$user) {
            return api_rr()->notFoundUser();
        }

        $operatorId = $this->getAuthAdminId();
        pocket()->tradeWithdraw->completeRecordByWithdraw(
            $user, $tradeWithdraw, $operatorId);

        return api_rr()->postOK([]);
    }

    /**
     * 打款
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function repay(Request $request) : JsonResponse
    {
        $id = $request->post('id');
        if (!$id) {
            return api_rr()->notFoundResult('缺少提现ID');
        }
        $tradeWithdraw = rep()->tradeWithdraw->getById($id);
        if ($tradeWithdraw) {
            $pre = pocket()->wgcYunPay->preRepay($tradeWithdraw->id);
            if (!$pre->getStatus()) {
                return api_rr()->notFoundResult($pre->getMessage());
            }
            $wgcJob = (new WgcJob($tradeWithdraw->id))
                ->onQueue('wgc_pay');
            dispatch($wgcJob);
        } else {
            return api_rr()->notFoundResult('提现id不存在~');
        }

        return api_rr()->postOK([]);
    }

    /**
     * 添加黑名单
     *
     * @param  BlacklistRequest  $request
     * @param                    $uuid
     *
     * @return JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function blackStore(BlacklistRequest $request, $uuid)
    {
        $user      = rep()->user->getByUUid($uuid);
        $type      = $request->post('type');
        $reason    = $request->post('reason', '');
        $remark    = $request->post('remark', '');
        $blackType = $request->post('black_type');
        $faceBlack = $request->post('face_black');
        $recallMsg = $request->post('is_recall_msg', false);
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        $exist = rep()->blacklist->m()
            ->whereIn('related_type', [Blacklist::RELATED_TYPE_OVERALL, Blacklist::RELATED_TYPE_CLIENT])
            ->where(function ($query) use ($user) {
                $query->where('related_id', (string)$user->id)->orWhere('user_id', $user->id);
            })->first();
        if ($blackType != 'face' && ($exist && ($exist->expired_at == 0 || $exist->expired_at - time() > 0))) {
            $message = '当前用户已经被拉黑，拉黑时间至：' . ($exist->expired_at == 0 ? '永久拉黑' : date('Y-m-d H:i:s',
                    $exist->expired_at));

            return api_rr()->forbidCommon($message);
        }
        switch ((int)$type) {
            case 1:
                $expiredAt    = time() + 86400 * 3;
                $actionResult = '拉黑3天';
                break;
            case 2:
                $expiredAt    = time() + 86400 * 7;
                $actionResult = '拉黑7天';
                break;
            case 3:
                $expiredAt    = time() + 86400 * 30;
                $actionResult = '拉黑30天';
                break;
            case 4:
                $expiredAt    = 0;
                $actionResult = '永久拉黑';
                break;
            default:
                return api_rr()->notFoundResult('未找到封禁类型');
        }
        if ($blackType == 'account') {
            $blacklistData = [
                'related_type' => Blacklist::RELATED_TYPE_OVERALL,
                'related_id'   => $user->id,
                'user_id'      => 0,
                'reason'       => $reason,
                'remark'       => $remark,
                'expired_at'   => $expiredAt
            ];
            rep()->blacklist->m()->create($blacklistData);
            $redisUserKey = config('redis_keys.blacklist.user.key');
            redis()->client()->zAdd($redisUserKey, $expiredAt, $user->id);
            pocket()->netease->userBlock($user->uuid);
        } elseif ($blackType == 'client') {
            $userDetail = rep()->userDetail->m()->where('user_id', $user->id)->first();
            if (!$userDetail || !$userDetail->client_id) {
                return api_rr()->notFoundResult('当前用户没有设备号');
            }
            $blacklistData = [
                'related_type' => Blacklist::RELATED_TYPE_CLIENT,
                'related_id'   => $userDetail->client_id,
                'user_id'      => $user->id,
                'reason'       => $reason,
                'remark'       => $remark,
                'expired_at'   => $expiredAt
            ];
            rep()->blacklist->m()->create($blacklistData);
            $redisKey = config('redis_keys.blacklist.client.key');
            redis()->client()->zAdd($redisKey, $expiredAt, $userDetail->client_id);
        } elseif ($blackType == 'all') {
            $userDetail = rep()->userDetail->m()->where('user_id', $user->id)->first();
            if (!$userDetail || !$userDetail->client_id) {
                return api_rr()->notFoundResult('当前用户没有设备号');
            }

            pocket()->blacklist->postGlobalBlockUser($user->id, $reason, $remark, $expiredAt);
        }
        if ($faceBlack == 'true') {
            $result = pocket()->user->faceBlack($user);
            if ($result->getStatus() == false) {
                return api_rr()->forbidCommon($result->getMessage());
            }
        }

        if ($recallMsg == 'true') {
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->user,
                'recallUserMsg',
                [$user->id]
            );
        }

        if ($expiredAt == 0 && in_array(Role::KEY_CHARM_GIRL, explode(',', $user->role))) {
            pocket()->account->cancelUserCharm($user);
        }
        rep()->operatorSpecialLog->setNewLog($uuid, '拉黑', $actionResult, '', $this->getAuthAdminId());

        return api_rr()->postOK([]);
    }

    /**
     * 黑名单列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function blackList(Request $request)
    {
        $limit    = $request->get('limit', 10);
        $page     = $request->get('page', 1);
        $uuid     = $request->get('uuid');
        $nickname = $request->get('nickname');
        if ($uuid || $nickname) {
            $user = rep()->user->m()
                ->when($uuid, function ($query) use ($uuid) {
                    $query->where('uuid', $uuid);
                })
                ->when($nickname, function ($query) use ($nickname) {
                    $query->where('nickname', $nickname);
                })->first();
        } else {
            $user = null;
        }
        $list     = rep()->blacklist->m()
            ->whereIn('related_type',
                [Blacklist::RELATED_TYPE_OVERALL, Blacklist::RELATED_TYPE_CLIENT, Blacklist::RELATED_TYPE_FACE])
            ->when($user, function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('related_id', $user->id)->where('related_type', Blacklist::RELATED_TYPE_OVERALL)->orWhere(function ($query) use ($user) {
                        $query->where('user_id', $user->id)->whereIn('related_type', [Blacklist::RELATED_TYPE_FACE, Blacklist::RELATED_TYPE_CLIENT]);
                    });
                });
            })
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('id')
            ->get();
        $faces    = rep()->facePic->m()->whereIn('id',
            $list->where('related_type', Blacklist::RELATED_TYPE_FACE)->pluck('related_id')->toArray())->get();
        $facePics = [];
        foreach ($faces as $face) {
            $facePics[$face->id] = cdn_url($face->base_map);
        }
        $users   = rep()->user->getByIds($list->pluck('related_id')->toArray());
        $userArr = [];
        foreach ($users as $item) {
            $userArr[$item->id] = $item->uuid;
        }
        $redisKey = config('redis_keys.blacklist.user.key');
        $blockArr = redis()->client()->zRange($redisKey, 0, -1, ['withscores' => true]);
        foreach ($list as $black) {
            $black->setAttribute('expire_time',
                $black->expired_at == 0 ? '永久' : date('Y-m-d H:i:s', $black->expired_at));
            $black->setAttribute('create_time', (string)$black->created_at);
            $black->setAttribute('uuid', key_exists($black->related_id,
                $userArr) ? (string)$userArr[$black->related_id] : (string)$black->related_id);
            if ($black->related_type == Blacklist::RELATED_TYPE_FACE) {
                $black->setAttribute('uuid', $facePics[$black->related_id]);
            }
        }
        $allCount = rep()->blacklist->m()
            ->whereIn('related_type', [Blacklist::RELATED_TYPE_OVERALL, Blacklist::RELATED_TYPE_CLIENT])
            ->when($user, function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('related_id', $user->id)->orWhere('user_id', $user->id);
                });
            })
            ->count();

        return api_rr()->getOK(['data' => $list->toArray(), 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 删除黑名单
     *
     * @param  BlackDelRequest  $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function blackDel(BlackDelRequest $request)
    {
        $id    = $request->post('id');
        $black = rep()->blacklist->getById($id);
        $black->delete();
        if ($black->related_type == Blacklist::RELATED_TYPE_OVERALL) {
            $redisKey = config('redis_keys.blacklist.user.key');
            redis()->client()->zRem($redisKey, $black->related_id);
            $user = rep()->user->getById($black->related_id);
            pocket()->netease->userUnblock($user->uuid);
        } elseif ($black->related_type == Blacklist::RELATED_TYPE_CLIENT) {
            $redisKey = config('redis_keys.blacklist.client.key');
            $user     = rep()->user->getById($black->user_id);
            redis()->client()->zRem($redisKey, $black->related_id);
        } elseif ($black->related_type == Blacklist::RELATED_TYPE_FACE) {
            $facePic = rep()->facePic->m()->where('id', $black->related_id)->first();
            $sface   = rep()->sfaceRecord->m()->where('url', $facePic->base_map)->first();
            if (!$sface) {
                return api_rr()->forbidCommon('当前人脸没有被拉黑');
            }
            $user = rep()->user->getById($black->user_id);
            $sface->delete();
            pocket()->aliGreen->sfaceDelFace($user->uuid, [$sface->face_id]);
        }
        rep()->operatorSpecialLog->setNewLog($user->uuid, '拉黑', '解除拉黑', '', $this->getAuthAdminId());

        return api_rr()->deleteOK([]);
    }

    /**
     * 发送反馈信息
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendReportMessage(Request $request)
    {
        $message    = $request->post('message');
        $type       = $request->post('type');
        $reportUuid = $request->post('report_uuid');
        $userUuid   = $request->post('user_uuid');
        if ($message == '') {
            return api_rr()->forbidCommon('请输入反馈内容');
        }
        $report = rep()->report->m()
            ->where('uuid', $reportUuid)
            ->first();
        if (!$report) {
            return api_rr()->forbidCommon('无法获取当前举报信息');
        }
        $user = rep()->user->m()
            ->where('uuid', $userUuid)
            ->first();
        pocket()->netease->msgSendMsg(config('custom.little_helper_uuid'), $userUuid, $message);
        //        if ($type == 'report') {
        //            pocket()->netease->msgSendMsg(config('custom.little_helper_uuid'), $userUuid, $message);
        //        } elseif ($type == 'reported') {
        //            $extension = [
        //                'option' => [
        //                    'badge' => false
        //                ]
        //            ];
        //            $msgData   = [
        //                'type' => NeteaseCustomCode::STRONG_REMINDER,
        //                'data' => [
        //                    'title'   => '举报处理通知',
        //                    'content' => $message
        //                ]
        //            ];
        //            pocket()->common->sendNimMsgQueueMoreByPocketJob(
        //                pocket()->netease,
        //                'msgSendCustomMsg',
        //                [config('custom.little_helper_uuid'), $userUuid, $msgData, $extension]
        //            );
        //        }
        $data = [
            'report_id'    => $report->id,
            'related_type' => $user->id == $report->user_id ? ReportFeedback::RELATED_TYPE_REPORT : ReportFeedback::RELATED_TYPE_REPORTED,
            'related_id'   => $user->id,
            'content'      => $message
        ];
        rep()->reportFeedback->m()->create($data);
        if ($report->related_type == Report::RELATED_TYPE_USER) {
            if ($user->id == $report->user_id) {
                rep()->operatorSpecialLog->setNewLog($userUuid, '被举报列表', '投诉用户处理', $message, $this->getAuthAdminId());
            } elseif ($user->id == $report->related_id) {
                rep()->operatorSpecialLog->setNewLog($userUuid, '被举报列表', '被投诉用户处理', $message, $this->getAuthAdminId());
            }
        } elseif ($report->related_type == Report::RELATED_TYPE_APP) {
            rep()->operatorSpecialLog->setNewLog($userUuid, '反馈', '回复', $message, $this->getAuthAdminId());
        }

        return api_rr()->postOK([]);
    }

    /**
     * 完成举报处理
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function finishReport(Request $request)
    {
        $reportUuid = $request->post('report_uuid');
        $reportTags = $request->post('report_tags', []);
        $tags       = rep()->tag->m()->whereIn('uuid', $reportTags)->where('type', Tag::TYPE_ADMIN_REPORT)->get();
        $report     = rep()->report->m()->where('uuid', $reportUuid)->first();
        $report->update(['status' => 100, 'remark' => implode(',', $tags->pluck('name')->toArray())]);
        if ($report->related_type == Report::RELATED_TYPE_USER) {
            $user = rep()->user->getById($report->user_id);
            rep()->operatorSpecialLog->setNewLog($user->uuid, '被举报列表', '处理完成', '', $this->getAuthAdminId());
        } elseif ($report->related_type == Report::RELATED_TYPE_APP) {
            $user = rep()->user->getById($report->related_id);
            rep()->operatorSpecialLog->setNewLog($user->uuid, '反馈', '已处理', '', $this->getAuthAdminId());
        }

        return api_rr()->postOK([]);
    }

    /**
     * 某人的举报列表
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return JsonResponse
     */
    public function getUserReport(Request $request, $uuid)
    {
        $limit = $request->get('limit', 10);
        $page  = $request->get('page', 1);
        $user  = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        $reportList = rep()->report->m()
            ->where('related_type', Report::RELATED_TYPE_USER)
            ->where('related_id', $user->id)
            ->with(['reportUser', 'reportedUser'])
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('id')
            ->get();
        $reportPic  = rep()->resource->m()
            ->where('related_type', Resource::RELATED_TYPE_REPORT)
            ->whereIn('related_id', $reportList->pluck('id')->toArray())
            ->get();
        $reports    = [];
        foreach ($reportPic as $pic) {
            $reports[$pic->related_id][] = $pic;
        }
        foreach ($reportList as $report) {
            $report->setAttribute('create_time', (string)$report->created_at);
            $report->setAttribute('pics', key_exists($report->id, $reports) ? $reports[$report->id] : []);
        }
        $allCount = rep()->report->m()
            ->where('related_type', Report::RELATED_TYPE_USER)
            ->where('related_id', $user->id)
            ->count();

        return api_rr()->getOK(['data' => $reportList->toArray(), 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 获取职业
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function job(Request $request)
    {
        $jobs = rep()->job->m()->select(['uuid', 'name'])->get();
        foreach ($jobs as $job) {
            $job->uuid = (string)$job->uuid;
        }

        return api_rr()->getOK($jobs);
    }

    /**
     * 后台发送强提醒消息
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function sendStrongRemind(Request $request)
    {
        $uuid    = $request->post('id');
        $title   = $request->post('title');
        $content = $request->post('content');
        $adminId = $this->getAuthAdminId();
        $user    = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        $extension  = [
            'option' => [
                'badge' => false
            ]
        ];
        $data       = [
            'type' => NeteaseCustomCode::STRONG_REMINDER,
            'data' => [
                'title'   => $title,
                'content' => $content
            ]
        ];
        $createData = [
            'type'      => AdminSendNetease::TYPE_STRONG_REMIND,
            'msg'       => json_encode($data),
            'target_id' => $user->id,
            'operator'  => $adminId
        ];
        pocket()->common->sendNimMsgQueueMoreByPocketJob(
            pocket()->netease,
            'msgSendCustomMsg',
            [config('custom.little_helper_uuid'), $uuid, $data, $extension]
        );
        rep()->adminSendNetease->m()->create($createData);
        rep()->operatorSpecialLog->setNewLog($user->uuid, '用户管理', '发送强提醒消息', '', $adminId);

        return api_rr()->postOK([]);
    }

    /**
     * 获取强通知列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function getStrongRemind(Request $request)
    {
        $limit = $request->get('limit', 10);
        $page  = $request->get('page', 1);
        $list  = rep()->adminSendNetease->m()
            ->select([
                'admin_send_netease.msg',
                'admin_send_netease.operator',
                'user.uuid',
                'admin_send_netease.created_at'
            ])
            ->where('admin_send_netease.type', AdminSendNetease::TYPE_STRONG_REMIND)
            ->join('user', 'admin_send_netease.target_id', '=', 'user.id')
            ->with(['operator'])
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();
        foreach ($list as $item) {
            $arr = json_decode($item->msg);
            $item->setAttribute('uuid', (string)$item->uuid);
            $item->setAttribute('title', $arr->data->title);
            $item->setAttribute('content', $arr->data->content);
            $item->setAttribute('create_time', (string)$item->created_at);
        }
        $count = rep()->adminSendNetease->m()
            ->where('type', AdminSendNetease::TYPE_STRONG_REMIND)
            ->count();

        return api_rr()->getOK(['data' => $list, 'all_count' => $count, 'limit' => $limit]);
    }

    /**
     * 获取某个用户的邀请列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function inviteUsers(Request $request) : JsonResponse
    {
        $limit   = $request->get('limit', 20);
        $userIds = [];
        if ($request->has('nickname') || $request->has('uuid')) {
            $userIds = rep()->user->getQuery()
                ->when($request->has('nickname'), function ($query) use ($request) {
                    $query->where('nickname', 'like', '%' . $request->nickname . '%');
                })->when($request->has('uuid'), function ($query) use ($request) {
                    $query->where('uuid', $request->uuid);
                })->pluck('id')->toArray();
        }

        $inviteRecords = rep()->inviteRecord->m()->select('id', 'user_id', 'target_user_id',
            'created_at')->when($userIds, function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds);
        })->where('type', InviteRecord::TYPE_USER_REG)
            ->orderBy('id', 'desc')->paginate($limit);

        $users = pocket()->inviteRecord->getInviteUsersAndBuildReward(
            $inviteRecords->pluck('user_id')->toArray(), $inviteRecords);

        return api_rr()->getOK([
            'data'      => $users,
            'all_count' => $inviteRecords->total(),
            'limit'     => $limit
        ]);
    }

    /**
     * 获取当前用户黑名单状态
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function getUserBlackStatus(Request $request)
    {
        $uuid     = $request->get('uuid', 0);
        $user     = rep()->user->m()->where('uuid', $uuid)->first();
        $blackStr = pocket()->blacklist->getBlackList($user);

        return api_rr()->getOK([
            'black_str' => $blackStr,
        ]);
    }

    /**
     * 特殊操作log列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function getSpecialLog(Request $request)
    {
        $limit      = $request->get('limit', 10);
        $page       = $request->get('page', 1);
        $startTime  = $request->post('start_time', '1970-01-01');
        $endTime    = $request->post('end_time', date('Y-m-d H:i:s', time()));
        $operatorId = $request->post('operator');
        $targetId   = $request->post('target_user_id');
        $action     = $request->post('action');
        $list       = rep()->operatorSpecialLog->m()
            ->whereBetween('created_at', [strtotime($startTime), strtotime($endTime)])
            ->when($operatorId, function ($query) use ($operatorId) {
                $query->where('admin_id', $operatorId);
            })
            ->when($targetId, function ($query) use ($targetId) {
                $query->where('target_user_id', $targetId);
            })
            ->when($action, function ($query) use ($action) {
                $query->where('action', $action);
            })
            ->with([
                'operator' => function ($query) {
                    $query->select(['id', 'name']);
                }
            ])
            ->when($page != -1, function ($query) use ($limit, $page) {
                $query->limit($limit)->offset(($page - 1) * $limit);
            })
            ->orderByDesc('id')
            ->get();
        foreach ($list as $item) {
            $item->setAttribute('create_time', (string)$item->created_at);
            $item->setAttribute('target_user_id', (string)$item->target_user_id);
        }
        $allCount = rep()->operatorSpecialLog->m()
            ->whereBetween('created_at', [strtotime($startTime), strtotime($endTime)])
            ->when($operatorId, function ($query) use ($operatorId) {
                $query->where('admin_id', $operatorId);
            })
            ->when($targetId, function ($query) use ($targetId) {
                $query->where('target_user_id', $targetId);
            })
            ->when($action, function ($query) use ($action) {
                $query->where('action', $action);
            })
            ->count();

        return api_rr()->getOK(['data' => $list, 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 获取所有运营账号
     *
     * @return JsonResponse
     */
    public function getAllOperator()
    {
        $roles = rep()->adminRole->m()->whereIn('name', ['运营一级', '运营二级'])->get();

        return api_rr()->getOK(
            rep()->admin->m()->select(['id', 'name'])->whereIn('role_id', $roles->pluck('id')->toArray())->get()
        );
    }

    /**
     * 标记魅力女生
     *
     * @param $uuid
     *
     * @return JsonResponse
     */
    public function markCharmGirl($uuid)
    {
        $user          = rep()->user->getByUUid($uuid);
        $markCharmGirl = mongodb('mark_charm_girl')->where('user_id', $user->id)->first();
        if ($markCharmGirl) {
            return api_rr()->forbidCommon('该用户已经被标记过了');
        }
        mongodb('mark_charm_girl')->insert(['user_id' => $user->id]);
        pocket()->user->syncColdStartUser($user->id);
        rep()->operatorSpecialLog->setNewLog($uuid, '魅力女生列表', '标记', '', $this->getAuthAdminId());

        return api_rr()->postOK([]);
    }

    /**
     * 根据微信获取用户信息
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function getUsersByWechat(Request $request)
    {
        $wechat   = $request->get('wechat');
        $limit    = $request->get('limit', 10);
        $page     = $request->get('page', 1);
        $userIds  = DB::table('wechat')
            ->where('wechat', $wechat)
            ->get()
            ->pluck('user_id')->toArray();
        $users    = rep()->user->m()
            ->select(['id', 'uuid', 'nickname', 'role', 'created_at'])
            ->whereIn('id', $userIds)
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();
        $blackIds = rep()->blacklist->m()
            ->where('related_type', Blacklist::RELATED_TYPE_OVERALL)
            ->get()->pluck('related_id')->toArray();
        $wechats  = DB::table('wechat')->whereIn('user_id', $userIds);
        if ((clone $wechats)->count() == 0) {
            return api_rr()->forbidCommon('当前微信未在使用');
        }
        foreach ($users as $user) {
            $nowWechat = (clone $wechats)->where('user_id', $user->id)->orderByDesc('id')->first();
            if (!$nowWechat) {
                continue;
            }
            $user->setAttribute('wechat', $nowWechat->wechat);
            $user->setAttribute('user_role', implode(',', array_map(function ($key) {
                return User::ROLE_ARR[$key];
            }, explode(',', $user->role))));
            $user->setAttribute('create_time', (string)$user->created_at);
            $user->setAttribute('is_black', in_array($user->id, $blackIds));
        }
        $count = DB::table('wechat')
            ->where('wechat', $wechat)
            ->count();

        return api_rr()->getOK(['data' => $users, 'all_count' => $count, 'limit' => $limit]);
    }

    /**
     * 用户人脸登录列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function userLoginRecord(Request $request)
    {
        $uuid        = $request->get('id');
        $limit       = $request->get('limit', 10);
        $page        = $request->get('page', 1);
        $loginRecord = rep()->loginFaceRecord->m()
            ->select(['user.uuid', 'login_face_record.face_pic', 'login_face_record.login_status', 'login_face_record.token', 'login_face_record.created_at'])
            ->join('user', 'login_face_record.user_id', '=', 'user.id')
            ->when($uuid, function ($query) use ($uuid) {
                $query->where('user.uuid', $uuid);
            })
            ->orderByDesc('login_face_record.id')
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();
        foreach ($loginRecord as $item) {
            $item->setAttribute('uuid', (string)$item->uuid);
            $item->setAttribute('face_pic', $item->face_pic != '' ? cdn_url($item->face_pic) : '');
            $item->setAttribute('login_status', LoginFaceRecord::LOGIN_STATUS_MAPPING[$item->login_status]);
            $item->setAttribute('create_time', (string)$item->created_at);
        }
        $count = rep()->loginFaceRecord->m()
            ->join('user', 'login_face_record.user_id', '=', 'user.id')
            ->when($uuid, function ($query) use ($uuid) {
                $query->where('user.uuid', $uuid);
            })
            ->count();

        return api_rr()->getOK(['data' => $loginRecord, 'all_count' => $count, 'limit' => $limit]);
    }

    /**
     * 人脸认证详情
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function loginRecordDetail(Request $request)
    {
        $token  = $request->get('token');
        $result = pocket()->aliYun->smartAuthResult($token);
        $data   = $result->getData();
        if (is_null($data)) {
            return api_rr()->getOK([]);
        }
        $resultObject  = $data['ResultObject'];
        $subCode       = $resultObject['SubCode'];
        $passedScore   = $resultObject['PassedScore'];
        $metaInfo      = json_decode($resultObject['MaterialInfo']);
        $netErrorScore = $metaInfo->riskInfo->score;
        $netErrorTag   = '';
        if ($netErrorScore > 0) {
            $netErrorTag = $metaInfo->riskInfo->tags;
        }
        $deviceErrorTag = $metaInfo->deviceInfo->tags;
        $faceError      = $metaInfo->verifyInfo->faceAttack;
        $faceScore      = $metaInfo->verifyInfo->faceComparisonScore;
        $resultData     = [
            'sub_code'         => $subCode,
            'pass_score'       => $passedScore,
            'net_error_score'  => $netErrorScore,
            'net_error_tag'    => $netErrorTag,
            'device_error_tag' => $deviceErrorTag,
            'face_error'       => $faceError,
            'face_score'       => $faceScore
        ];

        return api_rr()->getOK($resultData);
    }
}

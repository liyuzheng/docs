<?php


namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SwitchModel;
use Illuminate\Http\Request;
use App\Models\UserReview;
use App\Models\Wechat;
use App\Models\UserAttrAudit;
use App\Http\Requests\Admin\CheckRequest;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Models\Resource;
use App\Constant\NeteaseCustomCode;
use App\Models\SfaceRecord;
use App\Models\UserSwitch;
use App\Jobs\UpdateUserInfoToMongoJob;
use App\Models\MemberRecord;
use App\Models\TradePay;
use App\Models\Trade;
use App\Models\TradeBuy;
use Illuminate\Http\JsonResponse;

class AccountController extends BaseController
{
    /**
     * 通过审核
     *
     * @param  CheckRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkPass(CheckRequest $request)
    {
        $uuid = $request->post('uuid');
        $user = rep()->user->m()->where('uuid', $uuid)->first();
        $type = $request->get('type');
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        switch ($type) {
            case 'auth':
                pocket()->account->adminSimpleCharmAuth($user, $this->getAuthAdminId());
                break;
            case 'update':
                $changeValues = $request->post();
                if (key_exists('change_key', $changeValues) && $changeValues['change_key']['key'] == 'nickname') {
                    rep()->userAttrAudit->m()
                        ->where('user_id', $user->id)
                        ->where('check_status', UserAttrAudit::STATUS_DELAY)
                        ->where('key', 'nickname')
                        ->update(['check_status' => UserAttrAudit::STATUS_PASS, 'done_at' => time()]);
                    rep()->user->m()
                        ->where('id', $user->id)
                        ->update(['nickname' => $changeValues['change_value']]);
                    pocket()->netease->userUpdateUinfo($user->uuid, $changeValues['change_value']);
                    $message = '昵称修改成功~';
                    pocket()->common->sendNimMsgQueueMoreByPocketJob(
                        pocket()->netease,
                        'msgSendMsg',
                        [config('custom.little_helper_uuid'), $user->uuid, $message]
                    );
                    rep()->operatorSpecialLog->setNewLog($uuid, '魅力女生资料修改审核列表', '昵称修改通过', '', $this->getAuthAdminId());
                }
                if (key_exists('change_key', $changeValues) && $changeValues['change_key']['key'] == 'intro') {
                    rep()->userAttrAudit->m()
                        ->where('user_id', $user->id)
                        ->where('check_status', UserAttrAudit::STATUS_DELAY)
                        ->where('key', 'intro')
                        ->update(['check_status' => UserAttrAudit::STATUS_PASS, 'done_at' => time()]);
                    rep()->userDetail->m()
                        ->where('user_id', $user->id)
                        ->update(['intro' => $changeValues['change_value']]);
                    $message = '个人简介修改成功~';
                    pocket()->common->sendNimMsgQueueMoreByPocketJob(
                        pocket()->netease,
                        'msgSendMsg',
                        [config('custom.little_helper_uuid'), $user->uuid, $message]
                    );
                    rep()->operatorSpecialLog->setNewLog($uuid, '魅力女生资料修改审核列表', '个人简介修改通过', '',
                        $this->getAuthAdminId());
                }
                if (key_exists('wechat', $changeValues) && $changeValues['wechat']) {
                    $wechat = rep()->wechat->m()->where('user_id', $user->id)->where('check_status',
                        UserAttrAudit::STATUS_DELAY)->orderBy('id', 'desc')->first();
                    rep()->wechat->m()->where('user_id', $user->id)->where('check_status', UserAttrAudit::STATUS_DELAY)
                        ->update(['check_status' => Wechat::STATUS_PASS, 'done_at' => time()]);
                    $switch     = rep()->switchModel->m()->where('key', SwitchModel::KEY_LOCK_WECHAT)->first();
                    $userSwitch = rep()->userSwitch->m()->where('user_id', $user->id)->where('switch_id',
                        $switch->id)->first();
                    if ($userSwitch && $userSwitch->status == UserSwitch::STATUS_ADMIN_LOCK) {
                        $userSwitch->update(['status' => UserSwitch::STATUS_CLOSE]);
                    }
                    $message = '修改的微信已审核成功~';
                    pocket()->common->sendNimMsgQueueMoreByPocketJob(pocket()->netease, 'msgSendMsg',
                        [config('custom.little_helper_uuid'), $user->uuid, $message]);
                    if (optional($user)->gender == User::GENDER_WOMEN
                        && pocket()->coldStartUser->isColdStartUser($user->id)) {
                        pocket()->coldStartUser->updateColdStartUserWeChat($user, $wechat);
                    }
                    rep()->operatorSpecialLog->setNewLog($uuid, '微信修改审核列表', '通过', '', $this->getAuthAdminId());
                }
                $job = (new UpdateUserInfoToMongoJob($user->id))->onQueue('update_user_info_to_mongo');
                dispatch($job);

                break;
        }


        return api_rr()->postOK([]);
    }

    /**
     * 不通过审核
     *
     * @param  CheckRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkFail(CheckRequest $request)
    {
        $uuid   = $request->post('uuid');
        $reason = $request->post('reason');
        $type   = $request->get('type');
        $user   = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        switch ($type) {
            case 'auth':
                pocket()->account->adminRefuseCharmAuth($user, $reason, $this->getAuthAdminId());
                break;
            case 'update':
                $changeValues = $request->post();
                if (key_exists('change_key', $changeValues) && $changeValues['change_key']['key'] == 'nickname') {
                    rep()->userAttrAudit->m()->where('user_id', $user->id)->where('check_status',
                        UserAttrAudit::STATUS_DELAY)
                        ->where('key', 'nickname')
                        ->update(['check_status' => UserAttrAudit::STATUS_FAIL]);
                    $message = '您的昵称修改被拒绝，拒绝理由:' . $reason;
                    pocket()->common->sendNimMsgQueueMoreByPocketJob(
                        pocket()->netease,
                        'msgSendMsg',
                        [config('custom.little_helper_uuid'), $user->uuid, $message]
                    );
                    rep()->operatorSpecialLog->setNewLog($uuid, '魅力女生资料修改审核列表', '昵称修改拒绝', $reason,
                        $this->getAuthAdminId());
                }
                if (key_exists('change_key', $changeValues) && $changeValues['change_key']['key'] == 'intro') {
                    rep()->userAttrAudit->m()->where('user_id', $user->id)
                        ->where('check_status', UserAttrAudit::STATUS_DELAY)
                        ->where('key', 'intro')
                        ->update(['check_status' => UserAttrAudit::STATUS_FAIL]);
                    $message = '您的个人简介修改被拒绝，拒绝理由:' . $reason;
                    pocket()->common->sendNimMsgQueueMoreByPocketJob(
                        pocket()->netease,
                        'msgSendMsg',
                        [config('custom.little_helper_uuid'), $user->uuid, $message]
                    );
                    rep()->operatorSpecialLog->setNewLog($uuid, '魅力女生资料修改审核列表', '个人简介修改拒绝', $reason,
                        $this->getAuthAdminId());
                }
                if (key_exists('wechat', $changeValues) && $changeValues['wechat']) {
                    rep()->wechat->m()
                        ->where('user_id', $user->id)
                        ->where('check_status', UserAttrAudit::STATUS_DELAY)
                        ->update(['check_status' => Wechat::STATUS_FAIL]);
                    $message = '您提交的微信修改被拒绝，拒绝理由:' . $reason;
                    pocket()->common->sendNimMsgQueueMoreByPocketJob(
                        pocket()->netease,
                        'msgSendMsg',
                        [config('custom.little_helper_uuid'), $user->uuid, $message]
                    );
                    rep()->operatorSpecialLog->setNewLog($uuid, '微信修改审核列表', '拒绝', $reason, $this->getAuthAdminId());
                }

                break;
        }


        return api_rr()->postOK([]);
    }

    /**
     * 忽略审核
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkIgnore(Request $request)
    {
        $uuid   = $request->post('uuid');
        $reason = $request->post('reason');
        $user   = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        $checkStatus = UserReview::CHECK_STATUS_IGNORE;
        $userReview  = rep()->userReview->m()
            ->where('user_id', $user->id)
            ->whereIn('check_status', [
                UserReview::CHECK_STATUS_DELAY,
                UserReview::CHECK_STATUS_BLACK_DELAY,
                UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE,
                UserReview::CHECK_STATUS_FOLLOW_WECHAT
            ])
            ->first();
        switch ($userReview->check_status) {
            case UserReview::CHECK_STATUS_DELAY:
            case UserReview::CHECK_STATUS_BLACK_DELAY:
                $action = '魅力女生审核列表';
                break;
            case UserReview::CHECK_STATUS_FOLLOW_WECHAT:
            case UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE:
                $action = '差一点完成审核的女生';
                break;
        }
        if ($userReview->check_status == UserReview::CHECK_STATUS_BLACK_DELAY) {
            $checkStatus = UserReview::CHECK_STATUS_BLACK_IGNORE;
        }
        $userReview->update(['check_status' => $checkStatus, 'reason' => $reason]);
        rep()->operatorSpecialLog->setNewLog($uuid, $action, '忽略', '', $this->getAuthAdminId());

        return api_rr()->postOK([]);
    }

    /**
     * 魅力女生审核列表
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function charms(Request $request)
    {
        $limit        = $request->get('limit', 10);
        $page         = $request->get('page', 1);
        $uuid         = $request->post('id');
        $mobile       = $request->post('mobile');
        $startTime    = $request->get('start_time', '1970-01-01');
        $endTime      = $request->get('end_time', date('Y-m-d H:i:s', time()));
        $type         = $request->post('type', 'default');
        $roles        = pocket()->role->getUserRoleArr(['charm_girl']);
        $checkUsers   = rep()->userReview->m()
            ->select([
                'user.id',
                'user_review.nickname',
                'user_review.created_at',
                'user_review.user_id',
                'user_review.check_status',
                'user_review.reason',
                'user_review.region'
            ])
            ->join('user', 'user_review.user_id', '=', 'user.id')
            ->whereNotIn('user.role', $roles)
            ->whereBetween('user_review.created_at', [strtotime($startTime), strtotime($endTime)])
            ->where('user.destroy_at', 0)
            ->when($type == 'default', function ($query) {
                $query->whereIn('check_status', [UserReview::CHECK_STATUS_DELAY, UserReview::CHECK_STATUS_BLACK_DELAY]);
            })
            ->when($type == 'ignore', function ($query) {
                $query->whereIn('check_status',
                    [UserReview::CHECK_STATUS_IGNORE, UserReview::CHECK_STATUS_BLACK_IGNORE]);
            })
            ->when($type == 'unfollow_wechat', function ($query) {
                $query->whereIn('check_status',
                    [UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE, UserReview::CHECK_STATUS_FOLLOW_WECHAT]);
            })
            ->when($uuid, function ($query) use ($uuid) {
                $query->where('user.uuid', $uuid);
            })
            ->when($mobile, function ($query) use ($mobile) {
                $query->where('user.mobile', $mobile);
            })
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('user_review.id')
            ->groupBy('user_id')
            ->get();
        $checkUserIds = $checkUsers->pluck('user_id')->toArray();
        $users        = rep()->user->getByIds($checkUserIds);
        $userArr      = [];
        foreach ($users as $user) {
            $userArr[$user->id] = [
                'uuid'       => $user->uuid,
                'mobile'     => $user->mobile,
                'created_at' => $user->created_at
            ];
        }
        $userReviewCounts = rep()->userReview->m()
            ->select(['user_id', DB::raw('count(*) as count')])
            ->whereIn('user_id', $checkUserIds)
            ->groupBy('user_id')
            ->get();
        $reviewCounts     = [];
        foreach ($userReviewCounts as $userReviewCount) {
            $reviewCounts[$userReviewCount->user_id] = $userReviewCount->count;
        }
        $result           = [];
        $blackUserWechats = pocket()->account->getBlockUserWechat();
        $userWechats      = rep()->wechat->m()->whereIn('user_id', $checkUserIds);
        /** 添加城市总数 **/
        $regionsHas   = array_unique($checkUsers->pluck('region')->toArray());
        $regionsGroup = rep()->user->m()
            ->select(['user_detail.region', DB::raw('count(user.id) as total')])
            ->join('user_detail', 'user_detail.user_id', '=', 'user.id')
            ->where('user.role', 'auth_user,charm_girl,user')
            ->whereIn('user_detail.region', $regionsHas)
            ->groupBy('user_detail.region')
            ->get();
        foreach ($regionsGroup as $key => $value) {
            $regionTotal[$value['region']] = $value['total'];
        }
        foreach ($checkUsers as $checkUser) {
            $userUsingWechat = $userWechats->where('user_id', $checkUser->user_id)->orderByDesc('id')->first();
            $regionCount     = key_exists($checkUser->region, $regionTotal) ? $regionTotal[$checkUser->region] : 0;
            $result[]        = [
                'uuid'            => (string)$userArr[$checkUser->user_id]['uuid'],
                'application_at'  => (string)$checkUser->created_at,
                'nickname'        => $checkUser->nickname,
                'create_at'       => (string)$userArr[$checkUser->user_id]['created_at'],
                'mobile'          => $userArr[$checkUser->user_id]['mobile'],
                'region'          => $checkUser->region,
                'region_total'    => $checkUser->region . ' ' . $regionCount,
                'is_black'        => in_array($checkUser->check_status, [
                    UserReview::CHECK_STATUS_BLACK_DELAY,
                    UserReview::CHECK_STATUS_BLACK_IGNORE,
                    UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE
                ]),
                'is_wechat_black' => $userUsingWechat && in_array($userUsingWechat->wechat, $blackUserWechats),
                'reason'          => $checkUser->reason,
                'review_count'    => $reviewCounts[$checkUser->user_id],
            ];
        }
        $allCount = rep()->userReview->m()
            ->join('user', 'user_review.user_id', '=', 'user.id')
            ->whereNotIn('user.role', $roles)
            ->whereBetween('user_review.created_at', [strtotime($startTime), strtotime($endTime)])
            ->where('user.destroy_at', 0)
            ->when($type == 'default', function ($query) {
                $query->whereIn('check_status', [UserReview::CHECK_STATUS_DELAY, UserReview::CHECK_STATUS_BLACK_DELAY]);
            })
            ->when($type == 'ignore', function ($query) {
                $query->whereIn('check_status',
                    [UserReview::CHECK_STATUS_IGNORE, UserReview::CHECK_STATUS_BLACK_IGNORE]);
            })
            ->when($type == 'unfollow_wechat', function ($query) {
                $query->whereIn('check_status',
                    [UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE, UserReview::CHECK_STATUS_FOLLOW_WECHAT]);
            })
            ->when($uuid, function ($query) use ($uuid) {
                $query->where('user.uuid', $uuid);
            })
            ->when($mobile, function ($query) use ($mobile) {
                $query->where('user.mobile', $mobile);
            })
            ->count();

        return api_rr()->getOK(['data' => $result, 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 认证女生审核详情
     *
     * @param $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function charmDetail($uuid)
    {
        $user = rep()->user->getByUUid($uuid);
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        $userId = $user->id;
        pocket()->user->appendToUser($user, ['avatar', 'photo' => $user]);
        $user        = $user->toArray();
        $charmDetail = rep()->userReview->m()
            ->where('user_id', $userId)
            ->whereIn('check_status', [
                UserReview::CHECK_STATUS_DELAY,
                UserReview::CHECK_STATUS_BLACK_DELAY,
                UserReview::CHECK_STATUS_IGNORE,
                UserReview::CHECK_STATUS_BLACK_IGNORE,
                UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE,
                UserReview::CHECK_STATUS_FOLLOW_WECHAT,
            ])
            ->with([
                'userPhotos' => function ($query) use ($userId) {
                    $query->where('related_type', Resource::RELATED_TYPE_USER_PHOTO);
                }
            ])
            ->first();
        if (!$charmDetail) {
            return api_rr()->forbidCommon('当前用户不存在详情');
        }
        $wechat = rep()->wechat->m()
            ->where('user_id', $userId)
            ->where('check_status', Wechat::STATUS_DELAY)
            ->first();
        $job    = rep()->job->m()->where('id', $charmDetail->job)->first();
        if (!$job) {
            return api_rr()->notFoundResult('当前job不存在');
        }
        $facePic   = rep()->facePic->m()->where('user_id', $charmDetail->user_id)->orderByDesc('id')->first();
        $blackFace = [];
        if (in_array($charmDetail->check_status, [
            UserReview::CHECK_STATUS_BLACK_DELAY,
            UserReview::CHECK_STATUS_BLACK_IGNORE,
            UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE,
        ])) {
            $result = pocket()->aliGreen->sfaceImageCompare($uuid, SfaceRecord::GROUP_FACE_BLACK);
            if ($result->getStatus() == false) {
                return api_rr()->forbidCommon('人脸检测失败');
            }
            $data      = $result->getData();
            $topPerson = $data['data'][0]['results'][0]['topPersonData'][0]['persons'];
            foreach ($topPerson as $item) {
                if (($item['rate'] * 100) > 90) {
                    $faceId         = $item['faceId'];
                    $blackFaceFirst = rep()->sfaceRecord->m()->where('face_id', $faceId)->first();
                    if ($blackFaceFirst) {
                        $blackFace[] = cdn_url($blackFaceFirst->url);
                    }
                }
            }
        }
        $userDetailExtra                    = rep()->userDetailExtra->m()
            ->where('user_id', $userId)
            ->with(['emotion', 'child', 'education', 'income', 'figure', 'smoke', 'drink'])
            ->first();
        $job                                = $job->toArray();
        $job['uuid']                        = (string)$job['uuid'];
        $user['nickname']                   = $charmDetail->nickname;
        $user['birthday']                   = $charmDetail->birthday;
        $user['userDetail']['region']       = $charmDetail->region;
        $user['userDetail']['height']       = $charmDetail->height;
        $user['userDetail']['weight']       = $charmDetail->weight;
        $user['userDetail']['intro']        = $charmDetail->intro;
        $user['job']                        = $job;
        $user['wechat']['number']           = $wechat->wechat;
        $user['wechat']['qr_code']          = $wechat->qr_code;
        $user['wechat']['parse_content']    = $wechat->parse_content;
        $user['wechat']['is_wechat_qrcode'] = ((strripos($wechat->parse_content, 'u.wechat.com') !== false));
        $user['face_pic']                   = [cdn_url($facePic->base_map)];
        $user['black_face']                 = $blackFace;
        $user['userDetailExtra']            = $userDetailExtra;

        return api_rr()->getOK($user);
    }

    /**
     * 取消魅力女生认证
     *
     * @param $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function charmDel($uuid)
    {
        $user = rep()->user->getByUUid($uuid);
        pocket()->account->cancelUserCharm($user);
        $data = [
            'type' => NeteaseCustomCode::DELETE_LOGIN,
            'data' => [
                'uuid' => $uuid
            ]
        ];
        pocket()->netease->msgSendCustomMsg(config('custom.little_helper_uuid'), $uuid, $data, ['option' => ['push' => false, 'badge' => false]]);
        rep()->operatorSpecialLog->setNewLog($uuid, '魅力女生列表', '取消魅力认证', '', $this->getAuthAdminId());

        return api_rr()->deleteOK([]);
    }

    /**
     * 魅力女生修改信息列表
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function charmUpdate(Request $request)
    {
        $id         = $request->get('id');
        $mobile     = $request->get('mobile');
        $limit      = $request->get('limit', 10);
        $page       = $request->get('page', 1);
        $checkUsers = rep()->userAttrAudit->m()
            ->select([
                'user_attr_audit.user_id',
                'user.uuid',
                'user.mobile',
                'user_attr_audit.key',
                'user_attr_audit.value'
            ])
            ->join('user', 'user_attr_audit.user_id', '=', 'user.id')
            ->whereIn('check_status', [UserAttrAudit::STATUS_DELAY])
            ->when($id, function ($query) use ($id) {
                $query->where('user.uuid', $id);
            })
            ->when($mobile, function ($query) use ($mobile) {
                $query->where('user.mobile', $mobile);
            })
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('user_attr_audit.id')
            ->get();
        $result     = [];
        foreach ($checkUsers as $checkUser) {
            $result[] = [
                'uuid'         => (string)$checkUser->uuid,
                'change_key'   => ['key' => $checkUser->key, 'show_name' => UserAttrAudit::KEY_ARR[$checkUser->key]],
                'change_value' => $checkUser->value,
                'mobile'       => (string)$checkUser->mobile
            ];
        }
        $allCount = rep()->userAttrAudit->m()
            ->where('check_status', UserAttrAudit::STATUS_DELAY)
            ->count();

        return api_rr()->getOK(['data' => $result, 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 魅力女生修改微信列表
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function wechatUpdate(Request $request)
    {
        $id           = $request->get('id');
        $mobile       = $request->get('mobile');
        $limit        = $request->get('limit', 10);
        $page         = $request->get('page', 1);
        $roles        = pocket()->role->getUserRoleArr(['charm_girl']);
        $checkWechats = rep()->wechat->m()
            ->select(['wechat.user_id', 'user.uuid', 'wechat.wechat', 'wechat.created_at', 'wechat.qr_code'])
            ->join('user', 'wechat.user_id', '=', 'user.id')
            ->whereIn('user.role', $roles)
            ->when($id, function ($query) use ($id) {
                $query->where('user.uuid', $id);
            })
            ->when($mobile, function ($query) use ($mobile) {
                $query->where('user.mobile', $mobile);
            })
            ->where('wechat.check_status', Wechat::STATUS_DELAY)
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('wechat.id')
            ->get();
        foreach ($checkWechats as $checkWechat) {
            $checkWechat->uuid = (string)$checkWechat->uuid;
        }
        $allCount = rep()->wechat->m()
            ->select(['wechat.user_id', 'wechat.wechat', 'wechat.created_at', 'wechat.qr_code'])
            ->join('user', 'wechat.user_id', '=', 'user.id')
            ->whereIn('user.role', $roles)
            ->where('wechat.check_status', Wechat::STATUS_DELAY)
            ->count();

        return api_rr()->getOK(['data' => $checkWechats, 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 手动发送消息
     *
     * @param  Request  $request
     * @param           $uuid
     */
    public function sendAuthMsg(Request $request, $uuid)
    {
        $type = $request->get('type');
        switch ($type) {
            case 'charm_pass':
                $message   = '恭喜认证成为魅力女生，你可以主动给男用户发消息。你的微信等私人信息只展示给vip付费用户请放心使用。
请您及时添加小圈专属客服微信为"xiaoquankefu01"，以便您在修改资料时能够及时联系到资料审核人员。';
                $data      = [
                    'type' => NeteaseCustomCode::CHARM_GIRL_AUTH,
                    'data' => ['status' => 'pass', 'message' => $message]
                ];
                $extention = ['pushcontent' => $message];
                pocket()->common->sendNimMsgQueueMoreByPocketJob(
                    pocket()->netease,
                    'msgSendCustomMsg',
                    [config('custom.little_helper_uuid'), $uuid, $data, $extention]
                );
                break;
            case 'charm_fail':
                $data = ['type' => 2, 'data' => ['status' => 'fail', 'message' => '$reason']];
                pocket()->common->sendNimMsgQueueMoreByPocketJob(pocket()->netease, 'msgSendCustomMsg',
                    [config('custom.little_helper_uuid'), $uuid, $data]
                );
                break;
        }

        return api_rr()->getOK([]);
    }

    /**
     * 后台锁微信
     *
     * @param $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function lockWechat($uuid)
    {
        $user       = rep()->user->getByUUid($uuid);
        $switch     = rep()->switchModel->m()->where('key', SwitchModel::KEY_LOCK_WECHAT)->first();
        $userSwitch = rep()->userSwitch->m()->where('user_id', $user->id)->where('switch_id', $switch->id)->first();
        if (!$userSwitch) {
            rep()->userSwitch->m()->create([
                'uuid'      => pocket()->util->getSnowflakeId(),
                'user_id'   => $user->id,
                'switch_id' => $switch->id,
                'status'    => UserSwitch::STATUS_ADMIN_LOCK
            ]);
        } else {
            if ($userSwitch->status != UserSwitch::STATUS_CLOSE) {
                return api_rr()->forbidCommon('当前用户微信已被隐藏');
            }
            $userSwitch->update(['status' => UserSwitch::STATUS_ADMIN_LOCK]);
        }
        pocket()->push->pushToUser($user, '多人反馈你的微信搜索不到、添加未成功，管理员隐藏了你的微信，快打开小圈恢复、修改吧！');
        pocket()->tengYu->sendAdminLockWechatMessage($user->mobile);
        rep()->operatorSpecialLog->setNewLog($uuid, '魅力女生列表', '隐藏微信', '', $this->getAuthAdminId());

        return api_rr()->postOK([]);
    }

    /**
     * 后台开启微信
     *
     * @param $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function openWechat($uuid)
    {
        $user       = rep()->user->getByUUid($uuid);
        $switch     = rep()->switchModel->m()->where('key', SwitchModel::KEY_LOCK_WECHAT)->first();
        $userSwitch = rep()->userSwitch->m()->where('user_id', $user->id)->where('switch_id', $switch->id)->first();
        if ($userSwitch && in_array($userSwitch->status, [UserSwitch::STATUS_OPEN, UserSwitch::STATUS_ADMIN_LOCK])) {
            $userSwitch->update(['status' => UserSwitch::STATUS_CLOSE]);
        }

        return api_rr()->postOK([]);
    }

    /**
     * 隐身
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function hideUser(Request $request, $uuid)
    {
        $user = rep()->user->getByUUid($uuid);
        $type = request('type', 'hide');
        if ($type === 'show') {
            $result = pocket()->account->showUser($user);
            if ($result->getStatus()) {
                return api_rr()->postOK([]);
            }

            return api_rr()->forbidCommon($result->getMessage());
        }
        $hide     = User::ADMIN_HIDE;
        $userLock = rep()->switchModel->m()
            ->where('key', SwitchModel::KEY_ADMIN_HIDE_USER)
            ->first();
        if (!$user || !$userLock) {
            return api_rr()->serviceUnknownForbid("系统错误~");
        }
        $userSwitchData = [
            'uuid'      => pocket()->util->getSnowflakeId(),
            'user_id'   => $user->id,
            'switch_id' => $userLock->id,
            'status'    => $userLock->default_status
        ];
        try {
            DB::transaction(function () use ($uuid, $hide, $userSwitchData) {
                rep()->userSwitch->m()->create($userSwitchData);
                rep()->user->m()->where('uuid', $uuid)->update([
                    'hide' => $hide
                ]);
            });
        } catch (\Exception $e) {
            return api_rr()->serviceUnknownForbid($e->getMessage());
        }

        $redisKey = config('redis_keys.hide_users.key');
        redis()->client()->sAdd($redisKey, $user->id);
        pocket()->esUser->updateUserFieldToEs($user->id, ['hide' => $hide]);
        rep()->operatorSpecialLog->setNewLog($uuid, '魅力女生列表', '隐身', '',
            $this->getAuthAdminId());

        if (optional($user)->gender == User::GENDER_WOMEN
            && pocket()->coldStartUser->isColdStartUser($user->id)) {
            pocket()->coldStartUser->updateColdStartUserSwitches($user,
                [SwitchModel::KEY_ADMIN_HIDE_USER => $hide]);
        }

        return api_rr()->postOK([]);
    }

    public function closeUserWeChatTrade(Request $request, $uuid)
    {
        $user       = rep()->user->getByUUid($uuid);
        $type       = $request->get('type', 'close');
        $status     = $type == 'close' ? 0 : 1;
        $switch     = rep()->switchModel->getQuery()->where('key',
            SwitchModel::KEY_CLOSE_WE_CHAT_TRADE)->first();
        $userSwitch = rep()->userSwitch->getQuery()->where('user_id', $user->id)
            ->where('switch_id', $switch->id)->first();
        if (!$userSwitch || $userSwitch->status != $status) {
            if (!$userSwitch) {
                $userSwitchData = [
                    'user_id'   => $user->id,
                    'switch_id' => $switch->id,
                    'uuid'      => pocket()->util->getSnowflakeId(),
                    'status'    => $status
                ];
                rep()->userSwitch->getQuery()->create($userSwitchData);
            } else {
                $userSwitch->update(['status' => $status]);
            }
        }

        return api_rr()->postOK([]);
    }

    /**
     * 用户消费明细
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userRechargeDetail(Request $request, $uuid)
    {
        $limit = $request->get('limit', 10);
        $page  = $request->get('page', 1);
        $user  = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        $result = [
            'member_time' => '',
            'recharge'    => [],
        ];
        $member = rep()->member->getUserValidMember($user->id);
        if ($member) {
            $result['member_time'] = date('Y-m-d H:i:s', ($member->start_at + $member->duration));
        }
        $userTrade       = rep()->trade->m()
            ->where('user_id', $user->id)
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderByDesc('id')
            ->get();
        $tradeBuyRecords = [];
        $tradeBuy        = rep()->tradeBuy->m()
            ->select(['trade_buy.id', 'user.uuid', 'trade_buy.created_at'])
            ->join('user', 'user.id', '=', 'trade_buy.target_user_id')
            ->whereIn('trade_buy.id', $userTrade
                ->whereIn('related_type', [
                    Trade::RELATED_TYPE_BUY_PRIVATE_CHAT,
                    Trade::RELATED_TYPE_BUY_WECHAT,
                    Trade::RELATED_TYPE_RED_PACKET_USER_PHOTO
                ])
                ->pluck('related_id')->toArray())
            ->get();
        foreach ($tradeBuy as $item) {
            $tradeBuyRecords[$item->id] = $item->toArray();
        }
        $refundRecords = [];
        $refunds       = rep()->unlockPreOrder->m()
            ->select(['unlock_pre_order.id', 'user.uuid'])
            ->join('user', 'user.id', '=', 'unlock_pre_order.target_user_id')
            ->where('unlock_pre_order.id', Trade::RELATED_TYPE_REFUND)
            ->get();
        foreach ($refunds as $refund) {
            $refundRecords[$refund->id] = $refund->toArray();
        }
        $tradeResult = [];
        foreach ($userTrade as $item) {
            switch ($item->related_type) {
                case Trade::RELATED_TYPE_BUY_PRIVATE_CHAT:
                    $data = [
                        'type'        => '购买私聊',
                        'amount'      => $item->amount / 100,
                        'target_uuid' => $tradeBuyRecords[$item->related_id]['uuid'],
                        'create_time' => date('Y-m-d H:i:s', $item->created_at->timestamp),
                    ];
                    break;
                case Trade::RELATED_TYPE_BUY_WECHAT:
                    $data = [
                        'type'        => '购买微信',
                        'amount'      => $item->amount / 100,
                        'target_uuid' => $tradeBuyRecords[$item->related_id]['uuid'],
                        'create_time' => date('Y-m-d H:i:s', $item->created_at->timestamp),
                    ];
                    break;
                case Trade::RELATED_TYPE_RECHARGE:
                    $data = [
                        'type'        => '充值钻石',
                        'amount'      => $item->amount / 100,
                        'target_uuid' => 0,
                        'create_time' => date('Y-m-d H:i:s', $item->created_at->timestamp),
                    ];
                    break;
                case Trade::RELATED_TYPE_RECHARGE_VIP:
                    $data = [
                        'type'        => '充值VIP',
                        'amount'      => $item->amount / 100,
                        'target_uuid' => 0,
                        'create_time' => date('Y-m-d H:i:s', $item->created_at->timestamp),
                    ];
                    break;
                case Trade::RELATED_TYPE_RED_PACKET_USER_PHOTO:
                    $data = [
                        'type'        => '钻石解锁视频',
                        'amount'      => $item->amount / 100,
                        'target_uuid' => $tradeBuyRecords[$item->related_id]['uuid'],
                        'create_time' => date('Y-m-d H:i:s', $item->created_at->timestamp),
                    ];
                    break;
                case Trade::RELATED_TYPE_REFUND:
                    $data = [
                        'type'        => '退回钻石',
                        'amount'      => $item->amount / 100,
                        'target_uuid' => $refundRecords[$item->related_id]['uuid'],
                        'create_time' => date('Y-m-d H:i:s', $item->created_at->timestamp),
                    ];
                    break;
                default:
                    continue 2;
            }
            $tradeResult[] = $data;
        }
        $result['recharge'] = $tradeResult;
        $allCount           = rep()->trade->m()
            ->where('user_id', $user->id)
            ->count();

        return api_rr()->getOK(['data' => $result, 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 用户免费解锁明细
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userFreeRechargeDetail(Request $request, $uuid)
    {
        $limit = $request->get('limit', 10);
        $page  = $request->get('page', 1);
        $user  = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        $tradeBuy    = rep()->tradeBuy->m()
            ->where('amount', 0)
            ->where('user_id', $user->id)
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();
        $targets     = [];
        $targetUsers = rep()->user->m()
            ->whereIn('id', $tradeBuy->pluck('target_user_id')->toArray())
            ->get();
        foreach ($targetUsers as $targetUser) {
            $targets[$targetUser->id] = $targetUser->uuid;
        }
        $result = [
            'member_time' => '',
            'recharge'    => [],
        ];
        $member = rep()->member->getUserValidMember($user->id);
        if ($member) {
            $result['member_time'] = date('Y-m-d H:i:s', ($member->start_at + $member->duration));
        }
        foreach ($tradeBuy as $item) {
            switch ($item->related_type) {
                case TradeBuy::RELATED_TYPE_BUY_PRIVATE_CHAT:
                    $data = [
                        'type'        => '购买私聊',
                        'amount'      => $item->amount / 100,
                        'target_uuid' => $targets[$item->target_user_id],
                        'create_time' => date('Y-m-d H:i:s', $item->created_at->timestamp),
                    ];
                    break;
                case TradeBuy::RELATED_TYPE_BUY_WECHAT:
                    $data = [
                        'type'        => '购买微信',
                        'amount'      => $item->amount / 100,
                        'target_uuid' => $targets[$item->target_user_id],
                        'create_time' => date('Y-m-d H:i:s', $item->created_at->timestamp),
                    ];
                    break;
            }
            $result['recharge'][] = $data;
        }
        $allCount = rep()->tradeBuy->m()
            ->where('amount', 0)
            ->where('user_id', $user->id)
            ->count();

        return api_rr()->getOK(['data' => $result, 'all_count' => $allCount, 'limit' => $limit]);
    }

    /**
     * 客服快捷回复话术创建添加
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     *
     */
    public function createCustomerServiceScript(Request $request)
    {
        $data = [
            "p_id" => 0,
            "code" => '',
            "type" => $request->get("type"),
            "name" => $request->get("name")
        ];
        rep()->option->createCustomerServiceScript($data);

        return api_rr()->postOK([]);
    }

    /**
     * 客户快捷回复话术查询
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     *
     */
    public function showCustomerServiceScript(Request $request) : JsonResponse
    {
        $data = rep()->option->showCustomerServiceScript($request->get('type'));
        $rs   = [
            "map"  => rep()->option->m()::TYPE_CONTENT,
            "type" => $request->get('type'),
            "name" => $data
        ];

        return api_rr()->getOK($rs);
    }

    /**
     * 客服快捷回复话术修改
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     *
     */
    public function updateCustomerServiceScript(Request $request)
    {
        $data = [
            'type' => $request->get('type'),
            'name' => $request->get('name')
        ];
        rep()->option->updateCustomerServiceScript($data, $request->get('old_name'));

        return api_rr()->putOK([]);
    }

    /**
     * 客服快捷回复话术修改
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     *
     */
    public function deleteCustomerServiceScript(Request $request)
    {
        $data = [
            'type' => $request->get('type'),
            'name' => $request->get('name')
        ];
        rep()->option->deleteCustomerServiceScript($data);

        return api_rr()->deleteOK([]);
    }

}

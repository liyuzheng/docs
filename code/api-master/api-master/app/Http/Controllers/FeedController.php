<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDetail;
use App\Jobs\GreetStaticJob;
use App\Jobs\EsGreetCountJob;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Feed\FeedGreetRequest;
use App\Http\Requests\Feed\FeedUserRequest;
use Illuminate\Http\Request;

/**
 * Class FeedController
 * @package App\Http\Controllers
 */
class FeedController extends BaseController
{
    /**
     * 用户feed流接口
     *
     * @param  FeedUserRequest  $request
     *
     * @return JsonResponse
     */
    public function users(FeedUserRequest $request)
    {
        $userId   = $this->getAuthUserId();
        $userUUID = $this->getAuthUserUUID();
        $authUser = rep()->user->getById($userId, ['id', 'gender']);

        $type        = $request->get('type', 'active_user');
        $sort        = $request->get('sort', 'common');
        $cityName    = $request->get('city_name', '');
        $isMember    = $request->get('is_member', 0);
        $fullPage    = $request->get('page', 0);
        $limit       = (int)$request->get('limit', 10);
        $realTimeLat = round($request->get('lat', 0), 10);
        $realTimeLng = round($request->get('lng', 0), 10);
        $pageExArr   = explode('-', $fullPage);
        foreach ($pageExArr as $item) {
            if (!is_numeric($item)) {
                return api_rr()->requestParameterError('参数错误');
            }
        }
        if ($cityName === '附近') {
            $cityName = "";
        }
        /*** common普通排序  except_inactive活跃优先  new_user 附近新入的女生*/
        $key = request('key');
        if ($key) {
            $type = 'lbs_user';
            switch ($key) {
                case 'lbs_online':
                    $sort = 'except_inactive';
                    break;
                case 'lbs_new':
                    $sort = 'new_user';
                    break;
                case 'lbs_charm_first':
                    $sort = 'charm_first';
                    break;
                case 'lbs_vip':
                    $isMember = 1;
                    break;
                case 'lbs_girl':
                    $type = 'charm_girl';
                    break;
                default:
                    break;
            }
        }
        $lastActiveAt = time();
        if ($fullPage !== 0 && str_contains($fullPage, '-')) {
            [$page, $lastActiveAt] = explode('-', $fullPage);
        } else {
            $page = $fullPage;
        }
        $appName = user_agent()->appName;
        $version = user_agent()->clientVersion;
        $channel = $this->getHeaderChannel();
        if ($authUser->gender === User::GENDER_WOMEN &&
            $isMember === 1 &&
            !pocket()->user->hasRole($authUser, Role::KEY_CHARM_GIRL)) {
            if (version_compare($version, '2.0.0', '<')) {
                return api_rr()->guideRecharge(trans('messages.woman_screen_vip_ntoice'), []);
            }
        }
        if (in_array($userUUID, pocket()->util->getIosAuditUUIds(), true)
            ||
            pocket()->version->whetherAndroidAuditing($appName, $version, $channel)) {
            $iosAuditUUIDS = pocket()->util->getIosAuditUserListUUIds();
            $users         = rep()->user->m()
                ->select(['user.id', 'uuid', 'number', 'nickname', 'gender', 'birthday', 'user.active_at', 'role'])
                ->whereIn('uuid', $iosAuditUUIDS)
                ->when($page, function ($query) use ($page) {
                    $query->where('user.id', '>', $page);
                })
                ->orderBy('id', 'asc')
                ->limit($limit)
                ->get();
            if ($users->isEmpty()) {
                return api_rr()->getOKnotFoundResultPaging(trans('messages.not_have_more_users'));
            }
            pocket()->user->appendToUsers($users,
                [
                    'avatar',
                    'photo',
                    'job',
                    'netease' => ['accid'],
                    'member',
                    'auth_user',
                    'charm_girl',
                    'user_detail',
                    'active'
                ]);
            $nextPage = $users->last()->id . '-' . $users->last()->active_at;
        } else {
            $role   = [15, 7, 11, 14, 3, 6, 10, 2];
            $field  = ['user.id', 'uuid', 'number', 'nickname', 'gender', 'birthday', 'user.active_at', 'role'];
            $gender = $authUser->gender === User::GENDER_WOMEN ?
                User::GENDER_MAN : User::GENDER_WOMEN;
            switch ($type) {
                /** 新入用户 */
                case 'new_user':
                    $query = rep()->user->m()
                        ->select($field)
                        ->where('gender', $gender)
                        ->where('destroy_at', 0)
                        ->where('hide', User::SHOW);
                    /** 新入魅力女生按照后台审核通过的时间排序 */
                    if ($gender === User::GENDER_WOMEN) {
                        $query->whereIn('user.role', $role)->orderBy('user.charm_girl_at', 'desc');
                    } else {
                        $query->orderBy('user.created_at', 'desc');

                        return api_rr()->getOKnotFoundResultPaging(trans('messages.not_have_more_users'));
                    }
                    $users = $query->limit($limit)->skip($limit * $page);
                    break;
                /** 魅力女生 */
                case 'charm_girl':
                    if ($type === 'charm_girl' && $authUser->gender == User::GENDER_WOMEN) {
                        //     $users = pocket()->user->getLbsUsers($userId, User::GENDER_WOMEN, $page, $limit, $sort, $field,
                        //     $cityName, $isMember, $version);
                        [$users, $lastPage] = pocket()->user->getLbsOnlineUsers(
                            $userId,
                            User::GENDER_WOMEN,
                            $fullPage,
                            $limit,
                            $sort,
                            $field,
                            $realTimeLng,
                            $realTimeLat,
                            $cityName,
                            $isMember,
                            $version,
                            true
                        );
                    } else {
                        $users = rep()->user->m()
                            ->select($field)
                            ->where('gender', User::GENDER_WOMEN)
                            ->where('destroy_at', 0)
                            ->whereIn('user.role', $role)
                            ->where('hide', User::SHOW)
                            ->where(function ($q) use ($page, $lastActiveAt) {
                                $q->where('user.active_at', '<', $lastActiveAt)
                                    ->orWhere(function ($q1) use ($page, $lastActiveAt) {
                                        $q1->where('user.active_at', $lastActiveAt)->where('user.id', '>', (int)$page);
                                    });
                            })
                            ->orderBy('user.active_at', 'desc')
                            ->orderBy('user.id', 'asc')
                            ->limit($limit);
                    }
                    break;
                /** 活跃用户 */
                case 'active_user':
                    $query = rep()->user->m()
                        ->select($field)
                        ->where('destroy_at', 0)
                        ->where('hide', User::SHOW);
                    if ($gender === User::GENDER_WOMEN) {
                        $query->whereIn('user.role', $role);
                    }
                    $users = $query
                        ->where('gender', $gender)
                        ->where(function ($q) use ($page, $lastActiveAt) {
                            $q->where('user.active_at', '<', $lastActiveAt)
                                ->orWhere(function ($q1) use ($page, $lastActiveAt) {
                                    $q1->where('user.active_at', $lastActiveAt)->where('user.id', '>', $page);
                                });
                        })
                        ->orderBy('user.active_at', 'desc')
                        ->orderBy('user.id', 'asc')
                        ->limit($limit);
                    break;
                /** 附近的人 */
                //                case 'lbs_user':
                //                    $users = pocket()->user->getLbsUsers($userId, $gender, $page, $limit, $sort, $field, $cityName,
                //                        $isMember, $version);
                //                    break;
                /** 在线优先 */
                case 'lbs_user':
                    if ($key == 'lbs_online') {
                        if (!$request->get('page')) {
                            pocket()->user->delExistsFeedLbsUsersId($userId);
                        }
                        $excludeUsersId = pocket()->user->getExistsFeedLbsUsersId($userId);
                        [$users, $lastPage, $searchUserIds] = pocket()->user->getLbsOnlineUsers(
                            $userId,
                            $gender,
                            $fullPage,
                            $limit,
                            $sort,
                            $field,
                            $realTimeLng,
                            $realTimeLat,
                            $cityName,
                            $isMember,
                            $version,
                            false,
                            $excludeUsersId
                        );
                        pocket()->user->postExistsFeedLbsUsersId($userId, time(), $searchUserIds);
                    } else {
                        $users = pocket()->user->getLbsUsers(
                            $userId,
                            $gender,
                            $page,
                            $limit,
                            $sort,
                            $field,
                            $realTimeLng,
                            $realTimeLat,
                            $cityName,
                            $isMember,
                            $version
                        );
                    }
                    break;
                default:
                    return api_rr()->getOKnotFoundResultPaging(trans('messages.not_have_more_users'));
                    break;
            }
            /** 打开屏蔽通讯录，互相屏蔽 */
            //            $blackUserIds = pocket()->blacklist->userBlackIds($userId);
            //            $blackUserIds = [];
            $users = $users->get();
            //            $users = $users->filter(function ($item) use ($blackUserIds, $userId) {
            //                if ($item->id == $userId || !in_array($item->id, $blackUserIds, true)) {
            //                    return $item;
            //                }
            //            })->values();
            if ($users->isEmpty()) {
                return api_rr()->getOKnotFoundResultPaging(trans('messages.not_have_more_users'));
            }
            if (in_array($type, ['active_user']) || ($type === 'charm_girl' && $authUser->gender == User::GENDER_MAN)) {
                $nextPage = $users->last()->id . '-' . $users->last()->active_at;
            } else {
                $nextPage = ++$page;
            }
            if ((in_array($type, ['lbs_user']) && $key == 'lbs_online') ||
                (in_array($type, ['charm_girl']) && $key === 'lbs_girl' && $authUser->gender == User::GENDER_WOMEN)
            ) {
                $nextPage = $lastPage;
            }

        }
        $append = [
            'avatar',
            'album'       => $authUser,
            'job',
            'netease'     => ['accid'],
            'member',
            'auth_user',
            'charm_girl',
            'user_detail',
            'active',
            'detail_info' => $authUser,
        ];
        if (!$authUser->isMember() && in_array($key, ['lbs_new', 'lbs_vip'])) {
            $append['blur_avatar'] = $authUser;
            $append[]              = 'blur_nickname';
        }
        if ($authUser->gender == User::GENDER_WOMEN && $key === 'lbs_girl') {
            $append['blur_avatar'] = $authUser;
        }
        if (!$realTimeLat || !$realTimeLng) {
            $append['distance'] = $authUser;
        }
        //直接从mysql获取(二选一)
        $data = pocket()->user->appendToUsers($users, $append);
        //从mongo拼数据(二选一)
        //        $data = pocket()->user->getFeedUsersByMongo($users, $append, $version);
        if ($realTimeLat && $realTimeLng) {
            $locationArr = pocket()->user->getDistanceToUsersByFixedLocationUUIDArr($users, $realTimeLat, $realTimeLng);
            foreach ($data as &$datum) {
                $datum['distance'] = $locationArr[$datum['uuid']];
            }
        }

        return api_rr()->getOK(pocket()->util->getPaginateFinalData($data, $nextPage));
    }

    /**
     * 小圈h5页面无登录访问列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function webUsers(Request $request)
    {
        $userId   = 1;
        $authUser = pocket()->user->getUserInfoById($userId)->getData();
        $type     = $request->get('type', 'active_user');
        /*** common普通排序  except_inactive活跃优先  new_user 附近新入的女生*/
        $sort        = $request->get('sort', 'common');
        $cityName    = $request->get('city_name', '');
        $isMember    = $request->get('is_member', 0);
        $realTimeLat = round($request->get('lat', 0), 10);
        $realTimeLng = round($request->get('lng', 0), 10);
        if ($cityName === '附近') {
            $cityName = "";
        }
        $key = request('key');
        if ($key) {
            $type = 'lbs_user';
            switch ($key) {
                case 'lbs_online':
                    $sort = 'except_inactive';
                    break;
                case 'lbs_new':
                    $sort = 'new_user';
                    break;
                case 'lbs_charm_first':
                    $sort = 'charm_first';
                    break;
                case 'lbs_vip':
                    $isMember = 1;
                    break;
                case 'lbs_girl':
                    $type = 'charm_girl';
                    break;
                default:
                    break;
            }
        }
        $page  = $request->get('page', 0);
        $limit = (int)$request->get('limit', 10);
        if ($page !== 0 && str_contains($page, '-')) {
            [$page, $lastActiveAt] = explode('-', $page);
        }
        $version = user_agent()->clientVersion;
        $field   = ['user.id', 'uuid', 'number', 'nickname', 'gender', 'birthday', 'user.active_at', 'role'];
        $gender  = User::GENDER_WOMEN;
        switch ($type) {
            /** 在线优先 */
            case 'lbs_user':
                if ($key === 'lbs_online') {
                    [$users, $lastPage, $searchUserIds] = pocket()->user->getLbsOnlineUsers(
                        $userId,
                        $gender,
                        $request->get('page'),
                        $limit,
                        $sort,
                        $field,
                        $realTimeLng,
                        $realTimeLat,
                        $cityName,
                        $isMember,
                        $version,
                        false,
                        []
                    );
                } else {
                    $users = pocket()->user->getLbsUsers(
                        $userId,
                        $gender,
                        $page,
                        $limit,
                        $sort,
                        $field,
                        $realTimeLng,
                        $realTimeLat,
                        $cityName,
                        $isMember,
                        $version
                    );
                }
                break;
            default:
                return api_rr()->getOKnotFoundResultPaging('没有更多的用户了~');
                break;
        }
        $users = $users->whereHas('userDetail', function ($q) {
            $q->where('reg_schedule', UserDetail::REG_SCHEDULE_FINISH);
        })->get();
        if ($users->isEmpty()) {
            return api_rr()->getOKnotFoundResultPaging('没有更多用户了~~~');
        }
        if ($type === 'active_user' || ($type === 'charm_girl')) {
            $nextPage = $users->last()->id . '-' . $users->last()->active_at;
        } else {
            $nextPage = ++$page;
        }
        if (($type === 'lbs_user' && $key === 'lbs_online') ||
            ($type === 'charm_girl' && $key === 'lbs_girl')
        ) {
            $nextPage = $lastPage;
        }
        $append = [
            'avatar',
            'album'   => $authUser,
            'job',
            'netease' => ['accid'],
            'member',
            'auth_user',
            'charm_girl',
            'user_detail',
            'active'
        ];
        if (!$authUser->isMember() && in_array($key, ['lbs_new', 'lbs_vip'])) {
            $append['blur_avatar'] = $authUser;
            $append[]              = 'blur_nickname';
        }
        if ($authUser->gender == User::GENDER_WOMEN && $key === 'lbs_girl') {
            $append['blur_avatar'] = $authUser;
        }
        if (!$realTimeLat || !$realTimeLng) {
            $append['distance'] = $authUser;
        }
        //web小圈【直接从mysql获取(二选一)】
        //$data = pocket()->user->appendToUsers($users, $append);
        //web小圈【从mongo拼数据(二选一)】
        $data = pocket()->user->getFeedUsersByMongo($users, $append, $version);
        if ($realTimeLat && $realTimeLng) {
            $locationArr = pocket()->user->getDistanceToUsersByFixedLocationUUIDArr($users, $realTimeLat, $realTimeLng);
            foreach ($data as &$datum) {
                $datum['distance'] = $locationArr[$datum['uuid']];
            }
        }

        return api_rr()->getOK(pocket()->util->getPaginateFinalData($data, $nextPage));
    }

    /**
     * 获取打招呼用户
     */
    public function greetUser()
    {
        /** 打招呼开关 */
        if (!config('custom.greet.is_open')) {
            return api_rr()->getOK([]);
        }
        $authUser    = $this->getAuthUser();
        $reviewCheck = rep()->userReview->m()->where('user_id', $authUser->id)->orderByDesc('id')->first();
        $alertStatus = ($reviewCheck && $reviewCheck->alert_status == 0) ? true : false;
        if ($alertStatus) {
            return api_rr()->getOK([]);
        }
        $hasGreet = rep()->greet->m()
            ->where('user_id', $authUser->id)
            ->whereBetween('created_at', [Carbon::today()->timestamp, time()])
            ->exists();
        if ($hasGreet) {
            return api_rr()->getOK([]);
        }
        $lng = $lat = 0;
        [$lng, $lat] = pocket()->user->getLocationByUserId($authUser->id);
        $userIds = pocket()->esUser->searchGreetUsers($authUser->id, $lng, $lat);
        $users   = rep()->user->m()->whereIn('id', $userIds)->get();
        $append  = [
            'avatar',
            'photo',
            'job',
            'netease'  => ['accid'],
            'member',
            'auth_user',
            'charm_girl',
            'user_detail',
            'distance' => $authUser,
            'active'
        ];
        pocket()->user->appendToUsers($users, $append);

        return api_rr()->getOK($users);
    }

    /**
     * 打招呼
     *
     * @param  FeedGreetRequest  $request
     *
     * @return JsonResponse
     */
    public function greet(FeedGreetRequest $request)
    {
        $time        = time();
        $authUser    = $this->getAuthUser();
        $userUUIDArr = request('uuids', []);
        $userUUID    = [];
        foreach ($userUUIDArr as $item) {
            $intUUid = (int)$item;
            if (!is_numeric($intUUid) || !$intUUid) {
                return api_rr()->requestParameterError('参数错误');
            }
            $userUUID[] = (int)$item;
        }
        if (!$userUUID) {
            return api_rr()->requestParameterError('参数错误');
        }
        $hasGreet = rep()->greet->m()
            ->where('user_id', $authUser->id)
            ->whereBetween('created_at', [Carbon::today()->timestamp, $time])
            ->exists();
        if ($hasGreet) {
            return api_rr()->forbidCommon(trans('messages.today_say_hello_notice'));
        }
        $users         = rep()->user->m()->whereIn('uuid', $userUUID)->get();
        $exceptUserIds = rep()->greet->m()
            ->where('user_id', $authUser->id)
            ->whereIn('target_id', $users->pluck('id')->toArray())
            ->pluck('target_id')
            ->toArray();
        $users         = $users->whereNotIn('id', $exceptUserIds);
        $data          = [];
        foreach ($users as $user) {
            $data[] = [
                'user_id'    => $authUser->id,
                'target_id'  => $user->id,
                'distance'   => pocket()->user->getDistanceUsers($authUser->id, $user->id),
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        rep()->greet->m()->insert($data);

        $msgs   = config('custom.greet.msg');
        $client = redis()->client();
        foreach ($users as $user) {
            $msg = $msgs[array_rand($msgs)];
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->netease,
                'msgSendMsg',
                [$authUser->uuid, $user->uuid, $msg]
            );
            $client->zAdd(config('redis_keys.greets.key'), $time,
                sprintf("%s_%s_%s", $authUser->id, $user->id, $time));
            $job = (new EsGreetCountJob($authUser->id, $user->id, $time))
                ->onQueue('es_greet_count')
                ->delay(Carbon::now()->addSeconds(3));
            dispatch($job);
            $greetJob = (new GreetStaticJob('greet', $user->id))->onQueue('greet_pay_static');
            dispatch($greetJob);
        }

        return api_rr()->postOK([]);
    }
}

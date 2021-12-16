<?php

namespace App\Http\Controllers;

use App\Http\Requests\Moment\MomentIndexRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\Topic;
use App\Models\Moment;
use App\Models\Report;
use App\Models\Banner;
use App\Models\UserLike;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Moment\MomentRequest;
use App\Http\Requests\Moment\MomentStoreRequest;
use App\Http\Requests\Moment\MomentReportRequest;
use App\Jobs\CheckMomentJob;

/**
 * Class MomentController
 * @package App\Http\Controllers
 */
class MomentController extends BaseController
{
    /**
     * 动态列表
     *
     * @param  MomentIndexRequest  $request
     *
     * @return JsonResponse
     */
    public function index(MomentIndexRequest $request) : JsonResponse
    {
        $userId   = $this->getAuthUserId();
        $authUser = $this->getAuthUser();
        $user     = rep()->user->getById($authUser->id);

        $topicUUid   = $request->get('topic_uuid', 0);
        $type        = $request->get('type', 'hot');
        $gender      = $request->get('gender', 0);
        $page        = $request->get('page', 0);
        $realTimeLat = round($request->get('lat', 0), 10);
        $realTimeLng = round($request->get('lng', 0), 10);
        $limit       = (int)$request->get('limit', 10);
        $pageExArr   = explode('-', $page);
        $os          = user_agent()->os;
        foreach ($pageExArr as $item) {
            if (!is_numeric($item)) {
                return api_rr()->requestParameterError('参数错误');
            }
        }

        $topicId  = 0;
        $appName  = user_agent()->appName;
        $version  = user_agent()->clientVersion;
        $channel  = $this->getHeaderChannel();
        $userUUID = $this->getAuthUserUUID();
        if ($topicUUid) {
            $topicId = rep()->topic->m()->where('uuid', $topicUUid)->value('id');
        }
        if (in_array($userUUID, pocket()->util->getIosAuditUUIds(), true)
            ||
            pocket()->version->whetherAndroidAuditing($appName, $version, $channel)) {
            $auditUsersId = rep()->user->getByUUids(pocket()->util->getIosAuditUserListUUIds())->pluck('id')->toArray();
            $moments      = rep()->moment->m()->whereIn('user_id', $auditUsersId)
                ->orderBy('id', 'desc')
                ->limit($limit)->skip($limit * $page)
                ->get();
        } else {
            $sorts      = $user->gender === User::GENDER_MAN ? [Moment::SORT_ALL, Moment::SORT_MAN]
                : [Moment::SORT_ALL, Moment::SORT_WOMEN];
            $topMoments = $moments = rep()->moment->m()
                ->where('check_status', Moment::CHECK_STATUS_PASS)
                ->whereIn('moment.sort', $sorts)
                ->get();
            switch ($type) {
                /** 热门 */
                case 'hot':
                    [$momentIds, $momentData] = pocket()->esMoment->getNormalMomentIds(
                        ['field' => 'lt', 'value' => 1000],
                        ['field' => 'like_count', 'value' => 'desc'],
                        $page * $limit,
                        $limit,
                        $gender,
                        $topicId,
                        true
                    );
                    if (!$momentIds) {
                        return api_rr()->getOKnotFoundResultPaging('没有更多动态了~');
                    }
                    $orderBy   = $momentIds;
                    $orderBy[] = -1;
                    $moments   = rep()->moment->m()->select(
                        'id',
                        'uuid',
                        'content',
                        'like_count',
                        'topic_id',
                        'user_id',
                        'city',
                        'lng',
                        'lat',
                        'created_at'
                    )->whereIn('id', $momentIds)
                        ->orderBy(DB::raw('FIND_IN_SET(moment.id, "' . implode(',', $orderBy) . '"' . ")"))
                        ->get();
                    foreach ($moments as $moment) {
                        $moment->gender = $momentData[$moment->id]['gender'];
                    }
                    break;
                /** 最新 */
                case 'new':
                    [$momentIds, $momentData] = pocket()->esMoment->getNormalMomentIds(
                        ['field' => 'lt', 'value' => 1000],
                        ['field' => 'created_at', 'value' => 'desc'],
                        $page * $limit,
                        $limit,
                        $gender,
                        $topicId,
                        true
                    );
                    if (!$momentIds) {
                        return api_rr()->getOKnotFoundResultPaging('没有更多动态了~');
                    }
                    $orderBy   = $momentIds;
                    $orderBy[] = -1;
                    $moments   = rep()->moment->m()->select(
                        'id',
                        'uuid',
                        'content',
                        'like_count',
                        'topic_id',
                        'user_id',
                        'city',
                        'lng',
                        'lat',
                        'created_at'
                    )->whereIn('id', $momentIds)
                        ->orderBy(DB::raw('FIND_IN_SET(moment.id, "' . implode(',', $orderBy) . '"' . ")"))
                        ->get();
                    foreach ($moments as $moment) {
                        $moment->gender = $momentData[$moment->id]['gender'];
                    }
                    break;
                /** 附近 */
                case 'lbs':
                    if (!$realTimeLat && !$realTimeLng) {
                        [$lon, $lat] = pocket()->user->getLocationByUserId($userId);
                    } else {
                        $lon = $realTimeLng;
                        $lat = $realTimeLat;
                    }
                    [$momentIds, $momentData] = pocket()->esMoment->getLbsMomentIds(
                        ['field' => 'lt', 'value' => 1000],
                        $page * $limit,
                        $limit,
                        $lon,
                        $lat,
                        $gender,
                        $topicId,
                        true
                    );
                    if (!$momentIds) {
                        return api_rr()->getOKnotFoundResultPaging('没有更多动态了~');
                    }
                    $orderBy   = $momentIds;
                    $orderBy[] = -1;
                    $moments   = rep()->moment->m()->select(
                        'id',
                        'uuid',
                        'content',
                        'like_count',
                        'topic_id',
                        'user_id',
                        'city',
                        'lng',
                        'lat',
                        'created_at'
                    )->whereIn('id', $momentIds)
                        ->orderBy(DB::raw('FIND_IN_SET(moment.id, "' . implode(',', $orderBy) . '"' . ")"))
                        ->get();
                    foreach ($moments as $moment) {
                        $moment->gender = $momentData[$moment->id]['gender'];
                    }
                    break;
                default:
                    return api_rr()->getOKnotFoundResultPaging('没有更多的动态了~');
            }
            //            $blackUserIds  = pocket()->blacklist->userBlackIds($userId);
            $blackUserIds  = [];
            $hiddenUserIds = pocket()->user->getHideUserByRedis();
            $excUserIds    = array_merge($blackUserIds, $hiddenUserIds);
            $moments       = $moments->whereNotIn('user_id', $excUserIds);
            if ($moments->isEmpty()) {
                return api_rr()->getOKnotFoundResultPaging('没有更多动态了~');
            }
        }
        foreach ($moments as $moment) {
            $moment->setAttribute('is_top', 0);
        }

        if ($os == 'ios') {
            if ($page == 0 && isset($topMoments)) {
                foreach ($topMoments as $topMoment) {
                    $topMoment->setAttribute('is_top', 1);
                    $moments->prepend($topMoment);
                }
            }
        }

        $append = [
            'image',
            'user',
            'human',
            'topic',
            'like' => $userId
        ];
        if (!$realTimeLat || !$realTimeLng) {
            $append['distance'] = $authUser;
        }
        pocket()->moment->appendToMoments($moments, $append);
        if ($realTimeLat && $realTimeLng) {
            $locationArr = pocket()->moment->getDistanceToMomentsByFixedLocationUUIDArr(
                $moments,
                $realTimeLat,
                $realTimeLng
            );
            foreach ($moments as &$datum) {
                $datum['distance'] = $locationArr[$datum->uuid];
            }
        }

        $nextPage = ++$page;

        return api_rr()->getOK(pocket()->util->getPaginateFinalData(array_values($moments->toArray()), $nextPage));
    }

    /**
     * 某个用户的动态列表
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     */
    public function userMoments(Request $request, int $uuid) : JsonResponse
    {
        $userId   = $this->getAuthUserId();
        $authUser = $this->getAuthUser();
        $user     = rep()->user->getByUUid($uuid);
        if (!$user) {
            return api_rr()->notFoundResult(trans('messages.user_not_exist'));
        }
        $page   = $request->get('page', 0);
        $limit  = (int)$request->get('limit', 10);
        $status = [Moment::CHECK_STATUS_PASS];
        if ($userId === $user->id) {
            $status[] = Moment::CHECK_STATUS_DELAY;
        }
        $moments = $moments = rep()->moment->m()
            ->select(['id', 'uuid', 'topic_id', 'lng', 'lat', 'city', 'like_count', 'user_id', 'content', 'created_at'])
            ->where('deleted_at', 0)
            ->where('user_id', $user->id)
            ->whereIn('check_status', $status)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->skip($limit * $page)
            ->get();

        pocket()->moment->appendToMoments($moments, [
            'image',
            'user',
            'human',
            'topic',
            'like'     => $userId,
            'distance' => $authUser,
        ]);
        $nextPage = ++$page;

        return api_rr()->getOK(pocket()->util->getPaginateFinalData(array_values($moments->toArray()), $nextPage));
    }

    /**
     * 动态详情
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     */
    public function moment(Request $request, int $uuid) : JsonResponse
    {
        $userId   = $this->getAuthUserId();
        $authUser = $this->getAuthUser();
        $moment   = rep()->moment->m()->select('id', 'user_id', 'topic_id', 'uuid', 'lng', 'lat',
            'content', 'like_count', 'sort', 'city', 'created_at')->where('uuid', $uuid)
            ->where('check_status', Moment::CHECK_STATUS_PASS)->first();
        if (!$moment) {
            return api_rr()->notFoundResult(trans('messages.moment_deleted'));
        }
        $moment->setAttribute('is_top', $moment->sort >= 1000 ? 1 : 0);
        pocket()->moment->appendToMoment($moment, [
            'image',
            'user',
            'human',
            'avatar',
            'topic',
            'like'     => $userId,
            'distance' => $authUser,
        ]);

        return api_rr()->getOK($moment);
    }

    /**
     * 喜欢我的动态 列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function likeList(Request $request) : JsonResponse
    {
        $page  = $request->get('page', 0);
        $limit = $request->get('limit', 10);
        $user  = $request->user();
        mongodb('user_mark')->where('_id', $user->id)->update(['moment_unread' => 0]);

        $likes = rep()->userLike->getQuery()->select('user_like.id', 'user_like.user_id', 'moment.id as moment_id',
            'user_like.created_at', 'moment.uuid', 'moment.content', 'moment.deleted_at')
            ->join('moment', 'moment.id', 'user_like.related_id')
            ->where('moment.user_id', $user->id)->where('user_like.related_type', UserLike::RELATED_TYPE_MOMENT)
            ->where('user_like.user_id', '!=', $user->id)->when($page, function ($query) use ($page) {
                $query->where('user_like.id', '<', $page);
            })->orderBy('user_like.id', 'desc')->limit($limit)->get();

        $likes    = pocket()->moment->bindMomentFollowRecords($likes,
            trans('messages.other_like_moment_tmpl'));
        $nextPage = $likes->isNotEmpty() ? $likes->last()->id : $page;

        return api_rr()->getOK(pocket()->util->getPaginateFinalData(
            $likes, $nextPage));
    }

    /**
     * 我喜欢的动态列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function followed(Request $request)
    {
        $page  = $request->get('page', 0);
        $limit = $request->get('limit', 10);
        $user  = $request->user();
        $likes = rep()->userLike->getQuery()->select('user_like.id', 'moment.user_id', 'moment.id as moment_id',
            'user_like.created_at', 'moment.uuid', 'moment.content', 'moment.deleted_at')
            ->join('moment', 'moment.id', 'user_like.related_id')
            ->where('user_like.user_id', $user->id)->where('user_like.related_type', UserLike::RELATED_TYPE_MOMENT)
            ->where('moment.user_id', '!=', $user->id)->when($page, function ($query) use ($page) {
                $query->where('user_like.id', '<', $page);
            })->orderBy('user_like.id', 'desc')->limit($limit)->get();

        $likes    = pocket()->moment->bindMomentFollowRecords($likes, trans('messages.other_like_moment_tmpl'));
        $nextPage = $likes->isNotEmpty() ? $likes->last()->id : $page;

        return api_rr()->getOK(pocket()->util->getPaginateFinalData(
            $likes, $nextPage));
    }

    /**
     * 点赞
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function like(Request $request, int $uuid) : JsonResponse
    {
        $uuid      = (int)$uuid;
        $authUser  = rep()->user->m()->where('id', $this->getAuthUserId())->first();
        $moment    = rep()->moment->getByUUID($uuid);
        $reviewing = pocket()->account->checkUserReviewing($authUser);
        if ($reviewing->getStatus() == false) {
            return api_rr()->forbidCommon(trans('messages.review_not_like'));
        }
        if (!$moment) {
            return api_rr()->notFoundResult(trans('messages.moment_not_exists'));
        }
        if (pocket()->moment->hasLike($moment->id, $authUser->id)) {
            try {
                DB::transaction(function () use ($moment, $authUser) {
                    rep()->moment->m()->where('id', $moment->id)->lockForUpdate()->first();
                    if (pocket()->moment->hasLike($moment->id, $authUser->id)) {
                        rep()->userLike->m()->where('related_id', $moment->id)->where('user_id', $authUser->id)
                            ->where('related_type', UserLike::RELATED_TYPE_MOMENT)
                            ->update(['deleted_at' => time()]);
                        rep()->moment->m()->where('id', $moment->id)->decrement('like_count');
                    }
                });
            } catch (\Exception $exception) {
                return api_rr()->serviceUnknownForbid(trans('messages.unlike_failed_error'));
            }
            mongodb('user_mark')->where('_id', $moment->user_id)->decrement('like_count');
            pocket()->common->commonQueueMoreByPocketJob(pocket()->moment, 'updateLikeCountToEs',
                [$moment->id]);

            return api_rr()->postOK([]);
        }
        try {
            DB::transaction(function () use ($moment, $authUser) {
                rep()->moment->m()->where('id', $moment->id)->lockForUpdate()->first();
                if (!pocket()->moment->hasLike($moment->id, $authUser->id)) {
                    rep()->userLike->m()->create([
                        'related_id'   => $moment->id,
                        'related_type' => UserLike::RELATED_TYPE_MOMENT,
                        'user_id'      => $authUser->id,
                    ]);
                    rep()->moment->m()->where('id', $moment->id)->increment('like_count');
                }
            });
        } catch (\Exception $exception) {
            return api_rr()->serviceUnknownForbid(trans('messages.like_failed_error'));
        }

        pocket()->common->commonQueueMoreByPocketJob(pocket()->moment, 'likeNetease',
            [$moment->user_id, $authUser->id]);
        pocket()->common->commonQueueMoreByPocketJob(pocket()->moment,
            'updateLikeCountToEs', [$moment->id]);

        return api_rr()->postOK([]);
    }

    /**
     * 取消点赞【已弃用】
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     */
    public function unlike(Request $request, int $uuid) : JsonResponse
    {
        $authUser = $this->getAuthUser();
        $moment   = rep()->moment->getByUUID($uuid);
        if (!$moment) {
            return api_rr()->notFoundResult(trans('messages.moment_not_exists'));
        }
        try {
            DB::transaction(function () use ($moment, $authUser) {
                rep()->moment->m()->where('id', $moment->id)->lockForUpdate()->first();
                if (pocket()->moment->hasLike($moment->id, $authUser->id)) {
                    rep()->userLike->m()->where('related_id', $moment->id)->where('user_id', $authUser->id)
                        ->where('related_type', UserLike::RELATED_TYPE_MOMENT)
                        ->update(['deleted_at' => time()]);
                    rep()->moment->m()->where('id', $moment->id)->decrement('like_count');
                }
            });
        } catch (\Exception $exception) {
            return api_rr()->serviceUnknownForbid(trans('messages.unlike_failed_error'));
        }
        mongodb('user_mark')->where('_id', $moment->user_id)->decrement('like_count');
        pocket()->common->commonQueueMoreByPocketJob(pocket()->moment,
            'updateLikeCountToEs', [$moment->id]);

        return api_rr()->postOK([]);
    }

    /**
     * 删除动态
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, int $uuid) : JsonResponse
    {
        $time   = time();
        $moment = rep()->moment->m()->where('uuid', $uuid)->first();
        if (!$moment) {
            return api_rr()->notFoundResult(trans('messages.moment_not_exists'));
        }
        rep()->moment->m()->where('uuid', $uuid)->update(['check_status' => Moment::CHECK_STATUS_USER_FAIL]);
        pocket()->esMoment->updateMomentFieldToEs($moment->id,
            ['check_status' => Moment::CHECK_STATUS_USER_FAIL, 'deleted_at' => $time]
        );
        rep()->userLike->m()->where('related_type', Moment::RELATED_TYPE_MOMENT)
            ->where('related_id', $moment->id)
            ->update(['deleted_at' => $time]);

        return api_rr()->deleteOK([]);
    }

    /**
     * 获取首页banner
     *
     * @param  MomentRequest  $request
     *
     * @return JsonResponse
     */
    public function banner(MomentRequest $request) : JsonResponse
    {
        $key           = request('key', 'hot');
        $clientVersion = user_agent()->clientVersion;
        if (!$clientVersion) {
            $clientVersion = '1.0.0';
        }
        $user        = $this->getAuthUser();
        $user        = rep()->user->getById($user->id);
        $userDeail   = rep()->userDetail->m()->where('user_id', $user->id)->first();
        $momentMenus = collect(config('custom.menu.moment'))
            ->where('version', '<=', version_to_integer($clientVersion))
            ->sortByDesc('version')
            ->first();
        if ($user->gender === User::GENDER_WOMEN) {
            $menus = $momentMenus['man'] ?? (object)[];
        } else {
            $menus = $momentMenus['women'] ?? (object)[];
        }
        $menus = collect($menus)->where('key', $key)->first();
        if ($menus && $menus['show_banner'] === 0) {
            return api_rr()->getOK([]);
        }
        if ($user->gender === User::GENDER_MAN) {
            if ($user->isMember()) {
                $role = Banner::ROLE_MEN_MEMBER;
            } else {
                $role = Banner::ROLE_MAN;
            }
        } else {
            if ($user->isMember()) {
                $role = Banner::ROLE_CHARM_GRIL_MEMBER;
            } else {
                $role = Banner::ROLE_CHARM_GRIL;
            }
        }
        $os        = ($userDeail && $userDeail->os === 'ios') ? [
            Banner::OS_ALL,
            Banner::OS_IOS
        ] : [
            Banner::OS_ALL,
            Banner::OS_ANDROID
        ];
        $banners   = rep()->banner->m()
            ->where('related_type', Banner::RELATED_TYPE_MOMENT)
            ->where('type', Banner::TYPE_INNER_BROWSER)
            ->where('version', '<=', version_to_integer($clientVersion))
            ->whereIn('os', $os)
            ->where('deleted_at', 0)
            ->where('publish_at', '>', 0)
            ->where(function ($q) {
                $q->where('expired_at', '>=', time())->orWhere('expired_at', 0);
            })
            ->orderBy('sort', 'desc')
            ->get()
            ->filter(function ($banner) use ($role) {
                $roleArr = explode(',', $banner->role);
                if (in_array($role, $roleArr, true)) {
                    return $banner;
                }
            });
        $resources = rep()->resource->m()->whereIn('id', $banners->pluck('resource_id')->toArray())->get();
        foreach ($resources as $resource) {
            $resource->setHidden([
                'id',
                'sort',
                'fake_cover',
                'small_cover',
                'resource',
                'created_at',
                'updated_at',
                'deleted_at'
            ]);
        }
        foreach ($banners as $banner) {
            $banner->setAttribute('preview', $resources->where('id', $banner->resource_id)->first() ?? (object)[]);
        }

        return api_rr()->getOK($banners->values());
    }

    /**
     * 发布动态
     *
     * @param  MomentStoreRequest  $request
     *
     * @return JsonResponse
     */
    public function momentStore(MomentStoreRequest $request)
    {
        $content     = $request->post('content');
        $topicUUid   = $request->post('topic');
        $photos      = $request->post('photos');
        $lng         = $request->post('lng', 0);
        $lat         = $request->post('lat', 0);
        $city        = pocket()->userDetail->getCityByLoc($lng, $lat);
        $userId      = $this->getAuthUserId();
        $user        = rep()->user->getById($userId);
        $isCharmGirl = in_array(Role::KEY_CHARM_GIRL, explode(',', $user->role));
        $isMember    = rep()->member->getUserValidMember($userId);
        $now         = time();

        $reviewing = pocket()->account->checkUserReviewing($user);
        if ($reviewing->getStatus() == false) {
            return api_rr()->forbidCommon(trans('messages.review_not_release_moment'));
        }

        if (!pocket()->account->getMomentWant($user, $isCharmGirl, $isMember)) {
            return api_rr()->forbidCommon(trans('messages.forbid_release_moment'));
        }

        if (count($photos) < 1 || count($photos) > 4) {
            return api_rr()->forbidCommon(trans('messages.upload_imgs_limit'));
        }
        if (mb_strlen($content, 'utf8') > 50) {
            return api_rr()->forbidCommon(trans('messages.moment_content_limit'));
        }

        $topic   = rep()->topic->m()->where('uuid', $topicUUid)->first();
        $topicId = $topic ? $topic->id : 0;

        DB::beginTransaction();
        $createData   = [
            'uuid'         => pocket()->util->getSnowflakeId(),
            'user_id'      => $userId,
            'topic_id'     => $topicId,
            'content'      => $content,
            'star'         => 0,
            'lng'          => $lng,
            'lat'          => $lat,
            'city'         => $city,
            'check_status' => Moment::CHECK_STATUS_DELAY,
            'operator_id'  => 0
        ];
        $momentId     = rep()->moment->m()->create($createData)->id;
        $photoDetails = pocket()->account->getImagesDetail($photos);
        if ($photoDetails->getStatus() == false) {
            return api_rr()->forbidCommon(trans('messages.get_img_info_failed_error'));
        }
        $photoDatas = $photoDetails->getData();
        foreach ($photoDatas as $key => $value) {
            $photoData[] = [
                'uuid'         => pocket()->util->getSnowflakeId(),
                'related_type' => Resource::RELATED_MOMENT,
                'related_id'   => $momentId,
                'type'         => Resource::TYPE_IMAGE,
                'resource'     => $key,
                'height'       => $value['height'],
                'width'        => $value['width'],
                'sort'         => 100,
                'created_at'   => $now,
                'updated_at'   => $now
            ];
        }
        rep()->resource->m()->insert($photoData);
        DB::commit();
        pocket()->esMoment->postMomentToEs($momentId, $lng, $lat);
        $job = (new CheckMomentJob($momentId))->onQueue('check_moment');
        dispatch($job);

        return api_rr()->postOK([]);
    }

    /**
     * 获取topic
     * @return JsonResponse
     */
    public function topics() : JsonResponse
    {
        $topics = rep()->topic->m()
            ->select(['name', 'uuid'])
            ->where('deleted_at', 0)
            ->where('status', Topic::STATUS_OPEN)
            ->orderBy('sort', 'desc')
            ->get();

        return api_rr()->getOK($topics);
    }

    /**
     * 获取某个topic的详情
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     */
    public function topicDetail(Request $request, int $uuid) : JsonResponse
    {
        $topic = rep()->topic->m()->select(['id', 'name', 'uuid', 'desc'])
            ->where('uuid', $uuid)->first();
        if ($topic) {
            $count = rep()->moment->m()->where('topic_id', $topic->id)
                ->where('check_status', Moment::CHECK_STATUS_PASS)->count();
            $topic->setAttribute('msg',
                sprintf(trans('messages.tips_moment_count_tmpl'), $count));
        }

        return api_rr()->getOK($topic);
    }

    /**
     * 举报动态
     *
     * @param  MomentReportRequest  $request
     * @param  int                  $uuid
     *
     * @return JsonResponse
     */
    public function report(MomentReportRequest $request, int $uuid)
    {
        $content  = $request->post('content');
        $photos   = $request->post('photos', []);
        $reportId = $this->getAuthUserId();
        $moment   = rep()->moment->m()->where('uuid', $uuid)->first();
        if (!$moment) {
            return api_rr()->notFoundResult(trans('messages.moment_deleted'));
        }
        $data         = rep()->report->m()->create([
            'uuid'         => pocket()->util->getSnowflakeId(),
            'related_type' => Report::RELATED_TYPE_MOMENT,
            'related_id'   => $moment->id,
            'user_id'      => $reportId,
            'reason'       => $content,
            'status'       => 100
        ]);
        $reportPhotos = [];
        foreach ($photos as $photo) {
            $reportPhotos[] = [
                'uuid'         => pocket()->util->getSnowflakeId(),
                'related_type' => Resource::RELATED_TYPE_MOMENT_REPORT,
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

        return api_rr()->postOK([]);
    }

    /**
     * 客户端主动拉取首页的等多人给你点赞
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function likeMsg(Request $request) : JsonResponse
    {
        $msgCount   = 0;
        $user       = $this->getAuthUser();
        $momentIds  = rep()->moment->m()->where('user_id', $user->id)->pluck('id')->toArray();
        $likeUser   = rep()->userLike->m()->whereIn('related_id', $momentIds)
            ->where('related_type', UserLike::RELATED_TYPE_MOMENT)->first();
        $likeUserId = 0;
        if ($likeUser) {
            $likeUserId = $likeUser->user_id;
        }
        $likeUser = rep()->user->m()->where('id', $likeUserId)->first();
        if (!$likeUser) {
            return api_rr()->notFoundResult(trans('messages.moment_not_have_like'));
        }
        $userMark = mongodb('user_mark')->where('_id', $user->id)->first();
        if ($userMark && isset($userMark['moment_unread'])) {
            $msgCount = $userMark['moment_unread'] > 0 ? $userMark['moment_unread'] : 0;
        }
        $msgCountStr = $msgCount > 999 ? '999+' : $msgCount;
        $message     = $msgCount > 1
            ? sprintf(trans("messages.others_like_moment_notice_tmpl"), $likeUser->nickname, $msgCountStr)
            : sprintf(trans('messages.other_like_moment_tmpl'), $likeUser->nickname);

        if (!$msgCount) {
            return api_rr()->notFoundResult(trans('messages.moment_not_have_like'));
        }

        return api_rr()->getOK([
            'message' => $message,
            'count'   => $msgCount
        ]);
    }
}

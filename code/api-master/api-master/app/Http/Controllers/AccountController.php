<?php


namespace App\Http\Controllers;


use App\Constant\ApiBusinessCode;
use App\Foundation\Handlers\Tools;
use App\Http\Requests\Account\SwitchTmpMsgRequest;
use App\Models\SwitchModel;
use App\Models\UserDetail;
use App\Models\UserPowder;
use Illuminate\Support\Facades\DB;
use App\Models\Resource;
use App\Models\Blacklist;
use Illuminate\Http\Request;
use App\Models\Wechat;
use App\Http\Requests\User\UserWechatRequest;
use App\Models\UserReview;
use App\Models\Role;
use App\Http\Requests\Check\CheckStoreRequest;
use App\Models\User;
use App\Http\Requests\Account\ComparePhotoRequest;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\UserPhoto;
use App\Models\ResourceCheck;
use App\Jobs\UpdateUserLocationToEsJob;
use App\Models\FacePic;
use App\Models\UserFollowOffice;
use App\Models\Tag;
use App\Models\UserVisit;
use App\Models\UserSwitch;
use App\Jobs\UpdateUserActiveAtJob;
use App\Jobs\UpdateUserFieldToEsJob;
use App\Jobs\UpdateUserInfoToMongoJob;
use App\Http\Requests\BlackList\BlacklistIndexRequest;
use App\Models\InviteRecord;


class AccountController extends BaseController
{
    /**
     * 获取用户信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userInfo()
    {
        $userId = $this->getAuthUserId();

        $userResp = pocket()->account->getAccountInfo($userId, user_agent()->clientVersion);
        $user     = $userResp->getData();
        if ($user->userDetail->reg_schedule != UserDetail::REG_SCHEDULE_FINISH
            && pocket()->inviteRecord->checkUserIsAppletInvite($user)) {
            $user->setAttribute('invite_channel', 1);
        }

        return api_rr()->getOK($user);
    }

    /**
     * 更新用户
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function userUpdate(Request $request, int $uuid)
    {
        $reqPost = $request->post();
        if (count($reqPost) == 0) {
            return api_rr()->requestParameterMissing('缺少请求参数');
        }
        //todo 这里的参数都要校验
        $userField            = ['nickname', 'birthday', 'gender'];
        $userDetailField      = ['height', 'weight', 'region', 'intro', 'invite_code'];
        $userDetailExtraField = ['emotion', 'child', 'education', 'income', 'figure', 'smoke', 'drink', 'hobby'];
        $userTagField         = ['relation', 'job'];
        $allowField           = array_merge($userField, $userDetailField, $userTagField, $userDetailExtraField);
        $reqPost              = $request->only($allowField);
        if (count($reqPost) == 0) {
            return api_rr()->requestParameterMissing(trans('messages.lack_request_params_error'));
        }
        $user = rep()->user->getByUUid($uuid);
        if (!$user) {
            return api_rr()->notFoundUser();
        }

        $userId     = $user->id;
        $userDetail = rep()->userDetail->getByUserId($userId);
        $userRole   = explode(',', $user->role);
        $reviewing  = pocket()->account->checkUserReviewing($user);
        if ($reviewing->getStatus() == false) {
            return api_rr()->forbidCommon(trans('messages.review_not_change_info'));
        }

        if (in_array(Role::KEY_CHARM_GIRL, $userRole)) {
            $result = pocket()->account->specialUpdate($user, $userDetail, $reqPost);
            if ($result->getStatus() == false) {
                return api_rr()->forbidCommon($result->getMessage());
            }
            $user = pocket()->account->getAccountInfo($userId, user_agent()->clientVersion);
            $job  = (new UpdateUserInfoToMongoJob($userId))->onQueue('update_user_info_to_mongo');
            dispatch($job);

            return api_rr()->putOK($user->getData());
        }

        $result = pocket()->account->simpleUpdate($userDetail,
            $user, $userDetailField, $userDetailExtraField, $reqPost);
        if ($result->getStatus() == false) {
            return api_rr()->forbidCommon($result->getMessage());
        }
        $user = pocket()->account->getAccountInfo($userId, user_agent()->clientVersion)->getData();
        if ($user->userDetail->reg_schedule != UserDetail::REG_SCHEDULE_FINISH
            && pocket()->inviteRecord->checkUserIsAppletInvite($user)) {
            $user->setAttribute('invite_channel', 1);
        }
        // 用户注册后赠送用户会员
        /*if (array_key_exists('gender', $reqPost) && $user->getData()->gender == User::GENDER_MAN) {
            $giveMembersJob = new GiveMembersJob($user->getData()->id);
            $giveMembersJob->onQueue('give_members');
            dispatch($giveMembersJob);
        }*/
        $job = (new UpdateUserInfoToMongoJob($userId))->onQueue('update_user_info_to_mongo');
        dispatch($job);

        return api_rr()->putOK($user);
    }

    /**
     * 用户上传资源
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function userResourceUpdate(Request $request)
    {
        $userResourceField = ['avatar', 'photos', 'force'];
        $reqPost           = $request->only($userResourceField);
        $userId            = $this->getAuthUserId();
        $user              = rep()->user->getById($userId);
        $force             = $request->post('force', false);
        $final             = [];
        $userRoles         = explode(',', $user->role);
        $now               = time();

        $reviewing = pocket()->account->checkUserReviewing($user);
        if ($reviewing->getStatus() == false) {
            return api_rr()->forbidCommon(trans('messages.review_not_change_avatar'));
        }

        if (isset($reqPost['avatar'])) {
            $avatar = pocket()->account->uploadUserAvatar($reqPost['avatar'], $userRoles, $user, $force);
            if ($avatar->getStatus() == false) {
                $data = $avatar->getData();
                if ($data && key_exists('status', $data) && $data['status'] == 'porn') {
                    return api_rr()->forbidCommon($avatar->getMessage());
                } else {
                    return api_rr()->picCompareFail($avatar->getMessage());
                }
            }
            $final['avatar'] = $avatar->getData();
        }
        pocket()->user->appendAvatarToUser($user);
        pocket()->netease->userUpdateUinfo(
            $user->uuid,
            $user->nickname,
            $user->avatar
        );
        if (isset($reqPost['photos'])) {
            $photo = pocket()->account->uploadUserPhoto($reqPost['photos'], $userRoles, $user, $now);
            if ($photo->getStatus() == false) {
                return api_rr()->forbidCommon($photo->getMessage());
            }
            $final['photos'] = $photo->getData();
        }

        return api_rr()->putOK(count($final) > 0 ? $final : (object)[]);
    }

    /**
     * 带阅后即焚和红包视频的用户上传资源
     *
     * @param  Request  $request
     *
     * @return ResultReturn|\Illuminate\Http\JsonResponse
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function userResourceUpdateV2(Request $request)
    {
        $userResourceField = ['avatar', 'photos', 'force'];
        $reqPost           = $request->only($userResourceField);
        $userId            = $this->getAuthUserId();
        $user              = rep()->user->getById($userId);
        $final             = [];
        $userRoles         = explode(',', $user->role);
        $now               = time();

        $reviewing = pocket()->account->checkUserReviewing($user);
        if ($reviewing->getStatus() == false) {
            return api_rr()->forbidCommon(trans('messages.review_not_change_avatar'));
        }

        if (isset($reqPost['avatar'])) {
            $force     = key_exists('force', $reqPost['avatar']) ? $reqPost['avatar']['force'] : false;
            $avatarUrl = $reqPost['avatar']['url'];
            $avatar    = pocket()->account->uploadUserAvatar($avatarUrl, $userRoles, $user, $force);
            if ($avatar->getStatus() == false) {
                $data = $avatar->getData();
                if ($data && key_exists('status', $data) && $data['status'] == 'porn') {
                    return api_rr()->forbidCommon($avatar->getMessage());
                } else {
                    return api_rr()->picCompareFail($avatar->getMessage());
                }
            }
            $final['avatar'] = $avatar->getData();
        }
        pocket()->user->appendAvatarToUser($user);
        pocket()->netease->userUpdateUinfo(
            $user->uuid,
            $user->nickname,
            $user->avatar
        );

        if (isset($reqPost['photos'])) {
            $typeMapping   = array_flip(Resource::TYPE_LIST);
            $existPhotos   = rep()->resource->m()
                ->where('related_id', $user->id)
                ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
                ->get()
                ->pluck('resource')->toArray();
            $resourceDatas = [];
            if (!in_array(Role::KEY_CHARM_GIRL, $userRoles)) {
                $result = pocket()->account->uploadPhotosManV2($reqPost['photos'], $existPhotos, $user, $now);
                if ($result->getStatus() == false) {
                    return api_rr()->forbidCommon($result->getMessage());
                }
            } else {
                $womanPhotos   = [];
                $userPhotoData = [];
                $existVideo    = rep()->resource->m()
                    ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
                    ->where('related_id', $userId)
                    ->where('type', Resource::TYPE_VIDEO)
                    ->first();
                foreach ($reqPost['photos'] as $item) {
                    if ($item['type'] == 'video' && $existVideo) {
                        return api_rr()->forbidCommon(trans('messages.only_allow_upload_noe_video'));
                    }
                    if (!in_array($item['url'], $existPhotos)) {
                        $resourceDatas[]           = [
                            'uuid'         => pocket()->util->getSnowflakeId(),
                            'related_type' => Resource::RELATED_TYPE_USER_PHOTO,
                            'related_id'   => $user->id,
                            'type'         => $item['type'] == 'video' ? Resource::TYPE_VIDEO : Resource::TYPE_IMAGE,
                            'resource'     => $item['url'],
                            'height'       => config('custom.check_resource.height'),
                            'width'        => config('custom.check_resource.width'),
                            'sort'         => $item['type'] == 'video' ? 200 : 100,
                            'created_at'   => $now,
                            'updated_at'   => $now
                        ];
                        $womanPhotos[$item['url']] = [
                            'type'     => $item['type'],
                            'pay_type' => key_exists($item['pay_type'], UserPhoto::EXTENSION_MAPPING) ?
                                UserPhoto::EXTENSION_MAPPING[$item['pay_type']] : 100
                        ];
                    }
                }
                if (count($resourceDatas) != 0) {
                    DB::beginTransaction();
                    rep()->resource->m()->insert($resourceDatas);
                    $newResources   = rep()->resource->m()
                        ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
                        ->where('related_id', $user->id)
                        ->whereNotIn('resource', $existPhotos)
                        ->get();
                    $womanPhotoUrls = array_keys($womanPhotos);
                    if (count($newResources->pluck('id')->toArray()) != count($womanPhotoUrls)) {
                        return api_rr()->forbidCommon(trans('messages.have_review_images_pls_later'));
                    }
                    $resourceArr = array_combine($newResources->pluck('id')->toArray(), $womanPhotoUrls);
                    foreach ($resourceArr as $key => $value) {
                        $checkDatas[]    = [
                            'related_type' => ResourceCheck::RELATED_TYPE_MAPPING[$typeMapping[$womanPhotos[$value]['type']]],
                            'related_id'   => $user->id,
                            'resource_id'  => $key,
                            'resource'     => $value,
                            'status'       => ResourceCheck::STATUS_DELAY,
                            'created_at'   => $now,
                            'updated_at'   => $now
                        ];
                        $userPhotoData[] = [
                            'user_id'      => $userId,
                            'resource_id'  => $key,
                            'related_type' => $womanPhotos[$value]['pay_type'],
                            'amount'       => UserPhoto::AMOUNT_MAPPING[$womanPhotos[$value]['pay_type']],
                            'status'       => UserPhoto::STATUS_CLOSE,
                            'created_at'   => $now,
                            'updated_at'   => $now
                        ];

                    }
                    rep()->resourceCheck->m()->insert($checkDatas);
                    rep()->userPhoto->m()->insert($userPhotoData);
                    rep()->userPhotoChangeLog->m()->insert($userPhotoData);
                    DB::commit();
                    pocket()->common->commonQueueMoreByPocketJob(
                        pocket()->account,
                        'checkAuthPhoto',
                        [$user, user_agent()->clientVersion]
                    );
                }
                $userPhotos  = rep()->userPhoto->m()
                    ->where('user_id', $userId)
                    ->get();
                $resources   = rep()->resource->m()
                    ->whereIn('id', $userPhotos->pluck('resource_id')->toArray())
                    ->orderByDesc('sort')
                    ->get();
                $payTypeData = [];
                foreach ($userPhotos as $item) {
                    $payTypeData[$item->resource_id] = $item->related_type;
                }
                foreach ($resources as $item) {
                    if ($payTypeData[$item->id] == UserPhoto::RELATED_TYPE_FIRE) {
                        $item->setAttribute('pay_type', 'fire');
                        $item->setAttribute('cover', $item->fake_cover . '?imageMogr2/blur/200x50');
                        $item->setAttribute('small_cover', $item->small_cover . '/blur/200x50');
                    } elseif ($payTypeData[$item->id] == UserPhoto::RELATED_TYPE_RED_PACKET) {
                        $item->setAttribute('pay_type', 'red_packet');
                        $item->setAttribute('cover', $item->fake_cover . '|imageMogr2/blur/10x10');
                        $item->setAttribute('small_cover', $item->small_cover . '|imageMogr2/blur/200x50');
                    } else {
                        $item->setAttribute('pay_type', 'free');
                        $item->setAttribute('cover', $item->fake_cover);
                    }
                }
                $final['photos'] = $resources;
            }
        }

        return api_rr()->postOK(count($final) > 0 ? $final : (object)[]);
    }

    /**
     * 更多资料标签
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailExtraTags()
    {
        $tags   = rep()->tag->m()
            ->select(['uuid', 'type', 'name'])
            ->whereIn('type', [
                Tag::TYPE_EMOTION,
                Tag::TYPE_CHILD,
                Tag::TYPE_EDUCATION,
                Tag::TYPE_INCOME,
                Tag::TYPE_FIGURE,
                Tag::TYPE_SMOKE,
                Tag::TYPE_DRINK,
                Tag::TYPE_HOBBY,
            ])
            ->get();
        $userId = $this->getAuthUserId();
        $user   = rep()->user->getById($userId);
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        $gender     = $user->gender;
        $jobs       = rep()->job->m()->select(['uuid', 'name'])->where('gender', $gender)->get();
        $tagResults = [];
        foreach ($tags as $tag) {
            $tagResults[Tag::DETAIL_EXTRA_MAPPING[$tag->type]][] = $tag;
        }
        $tagResults['job'] = $jobs;

        return api_rr()->getOK($tagResults);
    }

    /**
     * 图片检测
     *
     * @param  ComparePhotoRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function comparePhoto(ComparePhotoRequest $request)
    {
        $photo = $request->post('photo');
        $uuid  = $request->post('uuid');
        $user  = rep()->user->getByUUid($uuid);
        if (!$user || $user->gender != User::GENDER_WOMEN) {
            return api_rr()->forbidCommon(trans('messages.curr_account_not_support_detect'));
        }
        $isRealFace = pocket()->aliYun->getDetectFaceResponse(cdn_url($photo));
        mongodb('detect_face')->insert([
            'user_id'        => $user->id,
            'path'           => $photo,
            'real_face_data' => $isRealFace->getData()
        ]);
        $realData = $isRealFace->getData();
        if (!$realData || count($realData['FaceInfos']['FaceAttributesDetectInfo']) == 0) {
            return api_rr()->forbidCommon(trans('messages.img_not_have_face'));
        }
        $basePic = pocket()->account->getBasePic($user->id);
        $result  = pocket()->aliYun->getCompareResponse(
            $basePic,
            cdn_url($photo)
        );
        if (!$result->getData()) {
            return api_rr()->forbidCommon('图片检测失败');
        }
        mongodb('photo_compare')->insert([
            'user_id'           => $user->id,
            'base_pic'          => $basePic,
            'compare_photo'     => cdn_url($photo),
            'compare_face_data' => $result->getData()
        ]);
        if ($result->getMessage() == 'No face detected from given images'
            || $result->getData()['SimilarityScore'] < User::ALIYUN_TEST_THRESHOLD) {
            return api_rr()->forbidCommon(trans('messages.img_not_pass_detect'));
        }

        return api_rr()->postOK([]);
    }

    /**
     * 获取用户钱包
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function wallet()
    {
        $userId                      = $this->getAuthUserId();
        $returnData                  = [];
        $wallet                      = rep()->wallet->getByUserId($userId);
        $returnData['balance']       = $wallet ? intval($wallet->balance / 10) : 0;
        $returnData['income']        = $wallet ? (int)floor($wallet->income / 100) : 0;
        $returnData['income_invite'] = $wallet ? $wallet->income_invite : 0;

        return api_rr()->getOK($returnData);
    }

    /**
     * 获取用户开关
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function switches()
    {
        $userId        = $this->getAuthUserId();
        $data          = rep()->userSwitch->m()
            ->where('user_id', $userId)
            ->get();
        $switchDetails = rep()->switchModel->m()
            ->whereIn('id', $data->pluck('switch_id')->toArray())
            ->get();
        $switchData    = [];
        foreach ($switchDetails as $switchDetail) {
            $switchData[$switchDetail->id]['key']  = $switchDetail->key;
            $switchData[$switchDetail->id]['name'] = $switchDetail->name;
        }
        $result = [];
        foreach ($data as $item) {
            $result[] = [
                'key'    => $switchData[$item->switch_id]['key'],
                'name'   => $switchData[$item->switch_id]['name'],
                'status' => (bool)$item->status
            ];
        }

        return api_rr()->getOK($result);
    }

    /**
     * 修改开关状态
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSwitches(Request $request)
    {
        $userId     = $this->getAuthUserId();
        $switches   = $request->post();
        $keys       = array_keys($switches);
        $userSwitch = rep()->userSwitch->m()
            ->join('switch', 'user_switch.switch_id', '=', 'switch.id')
            ->where('user_switch.user_id', $userId)
            ->whereIn('switch.key', $keys)->first();
        rep()->userSwitch->m()->where('id', $userSwitch->id)->update(
            ['status' => $switches[$userSwitch->key]]);
        $status = rep()->userSwitch->m()->where('id', $userSwitch->id)->first()->status;
        if (in_array(SwitchModel::KEY_LOCK_PHONE, $keys, true)) {
            pocket()->userSwitch->setUserSwitchCache($userId);
        }

        return api_rr()->putOK(['status' => (bool)$status]);
    }

    /**
     * 获取用户评价
     *
     * @param  int  $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function evaluate(int $uuid)
    {
        $user = rep()->user->getByUUid($uuid);
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        $evaluates = rep()->userEvaluate->m()
            ->select(DB::raw('tag_id,avg(star) as star'))
            ->where('target_user_id', $user->id)
            ->with(['tag'])
            ->groupBy('tag_id')
            ->get();
        foreach ($evaluates as $evaluate) {
            $evaluate->setAttribute('star', pocket()->account->getShowStar($evaluate->star));
            if (version_compare(user_agent()->clientVersion, '2.1.0', '<')) {
                $evaluate->setAttribute('star', intval($evaluate->star));
            }
        }
        $count           = rep()->userEvaluate->m()
            ->select(['user_id'])
            ->where('target_user_id', $user->id)
            ->groupBy('user_id')
            ->get();
        $result['count'] = count($count) > 6 ? '6+' : (string)count($count);
        $result['data']  = $evaluates;

        return api_rr()->getOK($result);
    }

    /**
     * 获取用户相册
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resource()
    {
        $userId    = $this->getAuthUserId();
        $resources = rep()->resource->m()
            ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
            ->where('related_id', $userId)
            ->get()
            ->toArray();

        return api_rr()->getOK($resources);
    }

    /**
     * 获取用户黑名单
     *
     * @param  BlacklistIndexRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function blacklist(BlacklistIndexRequest $request)
    {
        $page       = $request->get('page', 0);
        $limit      = $request->get('limit', 10);
        $userId     = $this->getAuthUserId();
        $blacklists = rep()->blacklist->m()->where('related_type', Blacklist::RELATED_TYPE_MANUAL)
            ->where('user_id', $userId)->orderByDesc('created_at')->skip($page * $limit)
            ->limit($limit)->pluck('related_id');
        if (blank($blacklists)) {
            return api_rr()->getOKnotFoundResultPaging(trans('messages.not_have_blacklist'));
        }
        $nextPage  = ++$page;
        $userInfos = pocket()->user->getUsersInfoByUserIds($blacklists->toArray());

        return api_rr()->getOK(pocket()->util->getPaginateFinalData(
            $userInfos->getData(), $nextPage));
    }

    /**
     * 获取关注当前用户的用户
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function followed(Request $request)
    {
        $page     = $request->get('page', 0);
        $limit    = $request->get('limit', 10);
        $user     = $request->user();
        $followed = rep()->userFollow->m()->join('user', 'user_follow.user_id', '=', 'user.id')
            ->where('user.hide', User::SHOW)->where('follow_id', $user->id)->skip($page * $limit)
            ->limit($limit)->orderByDesc('user_follow.created_at')->pluck('user_id');

        if (blank($followed)) {
            return api_rr()->getOKnotFoundResultPaging(trans('messages.not_have_more'));
        }
        $nextPage  = ++$page;
        $userInfos = pocket()->user->getUsersInfoByUserIds($followed->toArray());
        $data      = $userInfos->getData();
        pocket()->user->appendToUsers($data,
            ['photo', 'job', 'member', 'auth_user', 'charm_girl', 'user_detail', 'distance' => $user, 'active']);

        return api_rr()->getOK(pocket()->util->getPaginateFinalData(
            $data, $nextPage));
    }

    /**
     * 获取当前用户关注的用户
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function follow(Request $request)
    {
        $page    = $request->get('page', 0);
        $limit   = $request->get('limit', 10);
        $user    = $request->user();
        $follows = rep()->userFollow->m()->join('user', 'user_follow.follow_id', '=', 'user.id')
            ->where('user.hide', User::SHOW)->where('user_id', $user->id)->skip($page * $limit)
            ->limit($limit)->orderByDesc('user_follow.created_at')->pluck('follow_id');

        if (blank($follows)) {
            return api_rr()->getOKnotFoundResultPaging(trans('messages.not_have_more'));
        }
        $nextPage  = ++$page;
        $userInfos = pocket()->user->getUsersInfoByUserIds($follows->toArray());
        $data      = $userInfos->getData();
        pocket()->user->appendToUsers($data,
            ['photo', 'job', 'member', 'auth_user', 'charm_girl', 'user_detail', 'distance' => $user, 'active']);

        return api_rr()->getOK(pocket()->util->getPaginateFinalData($data, $nextPage));
    }

    /**
     * 上传用户通讯录
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mobileBook(Request $request)
    {
        return api_rr()->postOK([]);
        $userId  = $this->getAuthUserId();
        $mobiles = $request->post();
        pocket()->common->commonQueueMoreByPocketJob(pocket()->user,
            'setMobileBlacklist', [$mobiles, $userId]);

        return api_rr()->postOK([]);
    }

    /**
     * 创建用户tag
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tags(Request $request)
    {
        $userId     = $this->getAuthUserId();
        $tags       = $request->post();
        $createData = [];
        foreach ($tags as $tag) {
            $createData[] = [
                'uuid'       => pocket()->util->getSnowflakeId(),
                'user_id'    => $userId,
                'tag_id'     => $tag['uuid'],
                'created_at' => time(),
                'updated_at' => time()
            ];
        }
        rep()->userTag->m()->insert($createData);


        return api_rr()->postOK([]);
    }

    /**
     * 删除图片
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function delResource(Request $request)
    {
        $uuids    = $request->post('uuids');
        $resource = rep()->resource->m()->whereIn('uuid', $uuids)->get();
        foreach ($resource as $item) {
            DB::transaction(function () use ($item) {
                rep()->resourceCheck->m()->where('resource_id', $item->id)->delete();
                rep()->userPhoto->m()->where('resource_id', $item->id)->delete();
                $item->delete();
            });
        }

        return api_rr()->deleteOK([]);
    }

    /**
     * 获取用户权限
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function powers()
    {
        $user = rep()->user->getById($this->getAuthUserId());
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        $genders   = [UserPowder::GENDER_COMMON, $user->getRawOriginal('gender')];
        $members   = [UserPowder::MEMBER_COMMON];
        $members[] = $user->isMember() ? UserPowder::MEMBER_VALID : UserPowder::MEMBER_INVALID;
        $roles     = array_merge([UserPowder::ROLE_COMMON], explode(',', $user->role));
        $powers    = rep()->userPower->getUserPowers($user, $genders, $members, $roles);

        $powersList = [];
        foreach ($powers as $power) {
            if ($power->getRawOriginal('type') == UserPowder::TYPE_BOOLEAN) {
                $powersList[$power->key] = $power->getRawOriginal('value')
                    && in_array($power->getRawOriginal('role'), $roles)
                    && in_array($power->getRawOriginal('gender'), $genders)
                    && in_array($power->getRawOriginal('member'), $members);
            } else {
                $powersList[$power->key] = $power->value;
            }
        }

        if ($user->getRawOriginal('gender') == User::GENDER_MAN) {
            if (isset($powersList['invite_web_url'])) {
                $inviteTestRecord = rep()->userAb->getUserInviteTestRecord($user);
                if ((!$inviteTestRecord || !$inviteTestRecord->inviteTestIsB()) ||
                    !version_compare('2.2.0', user_agent()->clientVersion, '<=')) {
                    $powersList['invite_web_url'] = '';
                }
            }

            if (isset($powersList['is_open_invite'])
                && pocket()->inviteRecord->isInvitePunishment($user->id)) {
                $powersList['is_open_invite'] = false;
            }
        }

        if (isset($powersList['is_watermark'])
            && version_compare(user_agent()->clientVersion, '2.0.0', '<')) {
            $powersList['is_watermark'] = (int)$powersList['is_watermark'];
        }

        return api_rr()->getOK($powersList);
    }

    /**
     * 获取用户微信
     *
     * @param  UserWechatRequest  $request
     * @param  int                $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function contact(UserWechatRequest $request, int $uuid)
    {
        $userId = $this->getAuthUserId();
        $type   = $request->get('type');
        $tUser  = rep()->user->getByUUid($uuid);
        switch ($type) {
            case 'wechat':
                $powerList = pocket()->account->getInteractivePower($userId, $tUser->id);
                if (!$powerList['is_show_wechat']) {
                    if (version_compare(user_agent()->clientVersion, '1.5.0', '<')) {
                        $switch = rep()->userSwitch->getQuery()->join('switch', 'switch.id', 'user_switch.switch_id')
                            ->select('user_switch.id', 'user_switch.status')->where('user_switch.user_id', $tUser->id)
                            ->where('switch.key', SwitchModel::KEY_LOCK_WECHAT)->first();
                        if ($switch && $switch->status) {
                            return api_rr()->forbidCommon(trans('messages.hide_wechat_pls_privite_chat'));
                        }
                    }

                    return api_rr()->forbidCommon(trans('messages.not_unlock_wechat'));
                }

                $wechat = rep()->wechat->m()->select(['wechat'])->where('check_status', Wechat::STATUS_PASS)
                    ->where('user_id', $tUser->id)->orderByDesc('id')->value('wechat');
                $result = ['wechat' => ['number' => $wechat]];

                return api_rr()->getOK($result);
                break;
        }
    }

    /**
     * 获取活体人脸认证token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFaceToken()
    {
        $userId     = $this->getAuthUserId();
        $userAvatar = rep()->resource->m()
            ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
            ->where('related_id', $userId)
            ->orderByDesc('id')
            ->first();
        if (!$userAvatar) {
            return api_rr()->notFoundResult(trans('messages.not_found_avatar_pls_re_upload'));
        }
        $avatarResource = cdn_url($userAvatar->resource);
        //        if ($appName != 'xiaoquan') {
        //            return api_rr()->forbidCommon('真人认证功能维护中，请12月8日18点后尝试。');
        //        }
        $result = pocket()->aliYun->getAuthResponse('token', $userAvatar->uuid, $avatarResource);
        if ($result->getStatus() == false) {
            return api_rr()->serviceUnknownForbid($result->getMessage());
        }

        $data       = $result->getData();
        $createData = [
            'user_id'    => $userId,
            'request_id' => $data['RequestId'],
            'token'      => $data['VerifyToken'],
            'biz_id'     => $userAvatar->uuid
        ];
        $exist      = rep()->faceRecord->m()->where('user_id', $userId);
        if (count($exist->get()) > 0) {
            $exist->delete();
        }
        rep()->faceRecord->m()->create($createData);

        return api_rr()->getOK($result->getData());
    }

    /**
     * v2版本获取人脸认证token
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     */
    public function getFaceTokenV2(Request $request)
    {
        $metaInfo = $request->get('meta_info');
        if (!$metaInfo) {
            return api_rr()->forbidCommon('缺少meta_info');
        }
        $userId     = $this->getAuthUserId();
        $userAvatar = rep()->resource->m()->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
            ->where('related_id', $userId)->orderByDesc('id')->first();
        if (!$userAvatar) {
            return api_rr()->notFoundResult(trans('messages.not_found_avatar_pls_re_upload'));
        }
        $avatarResource = cdn_url($userAvatar->resource);
        $bizId          = 'xiaoquan' . $userAvatar->uuid . '/' . (string)rand(1000, 9999);
        $result         = pocket()->aliYun->smartAuthResponse($avatarResource, $userId, $bizId, $metaInfo);
        if ($result->getStatus() == false) {
            return api_rr()->serviceUnknownForbid($result->getMessage());
        }
        $data       = $result->getData();
        $createData = [
            'user_id'    => $userId,
            'request_id' => $data['RequestId'],
            'token'      => $data['ResultObject']['CertifyId'],
            'biz_id'     => $bizId
        ];
        $exist      = rep()->faceRecord->m()->where('user_id', $userId);
        if (count($exist->get()) > 0) {
            $exist->delete();
        }
        rep()->faceRecord->m()->create($createData);

        return api_rr()->getOK($result->getData());
    }

    /**
     * 获取人脸认证结果
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function getFaceResult()
    {
        $userId     = $this->getAuthUserId();
        $userAvatar = rep()->resource->m()
            ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
            ->where('related_id', $userId)
            ->orderByDesc('id')
            ->first();
        $result     = pocket()->aliYun->getAuthResponse('result', $userAvatar->uuid);
        if ($result->getStatus() == false) {
            return api_rr()->serviceUnknownForbid($result->getMessage());
        }

        return api_rr()->getOK($result->getData());
    }

    /**
     * 提交审核资料
     *
     * @param  CheckStoreRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStore(CheckStoreRequest $request)
    {
        $userId   = $this->getAuthUserId();
        $user     = rep()->user->getById($userId);
        $userRole = explode(',', $user->role);
        if (!in_array(Role::KEY_AUTH_USER, $userRole)) {
            return api_rr()->forbidCommon(trans('messages.undone_real_person_detect'));
        }
        $userReviewResult = rep()->userReview->m()->where('user_id', $userId)
            ->where('check_status', UserReview::CHECK_STATUS_DELAY)->first();
        if ($userReviewResult) {
            return api_rr()->forbidCommon(trans('messages.submitted_detect_pls_wait'));
        }
        $userReviewArr = pocket()->account->getCharmAuthWant($user);
        if ($userReviewArr['status'] == false) {
            return api_rr()->forbidCommon($userReviewArr['msg']);
        }
        $type   = $request->get('type');
        $result = DB::transaction(function () use ($type, $request, $userId, $user) {
            $createData = [];
            switch ($type) {
                case 'all':
                    $userDetail  = rep()->userDetail->m()->where('user_id', $userId)->lockForUpdate()->first();
                    $userJob     = rep()->userJob->m()->where('user_id', $userId)->orderByDesc('id')->first();
                    $height      = $request->post('height');
                    $weight      = $request->post('weight');
                    $intro       = $request->post('intro');
                    $jobId       = $request->post('job') ? rep()->job->getByUUID($request->post('job'),
                        ['id'])->id : $userJob->job_id;
                    $checkStatus = UserReview::CHECK_STATUS_DELAY;
                    if (version_compare(user_agent()->clientVersion, '2.0.0', '>=')) {
                        $isFollowWechat = rep()->userFollowOffice->m()
                            ->where('user_id', $userId)
                            ->first();
                        if (!$isFollowWechat || $isFollowWechat->status == UserFollowOffice::STATUS_DEFAULT) {
                            $checkStatus = UserReview::CHECK_STATUS_FOLLOW_WECHAT;
                        }
                    }
                    $alertStatus = (version_compare(user_agent()->clientVersion, '2.1.0',
                        '<')) ? UserReview::ALERT_STATUS_PASSIVE : UserReview::ALERT_STATUS_ACTIVE;
                    $createData  = [
                        'user_id'      => $userId,
                        'nickname'     => $user->nickname,
                        'birthday'     => $user->birthday,
                        'region'       => $userDetail->region,
                        'height'       => $height,
                        'weight'       => $weight,
                        'job'          => $jobId,
                        'intro'        => $intro,
                        'check_status' => $checkStatus,
                        'alert_status' => $alertStatus,
                    ];
                    break;
                case 'wechat':
                    $reviewing = pocket()->account->checkUserReviewing($user);
                    if ($reviewing->getStatus() == false) {
                        return api_rr()->forbidCommon(trans('messages.review_not_change_wechat'));
                    }
                    $existWechat = rep()->wechat->m()
                        ->where('check_status', Wechat::STATUS_DELAY)
                        ->where('user_id', $userId)
                        ->first();
                    if ($existWechat) {
                        return ResultReturn::failed(trans('messages.wechat_detect_not_change'));
                    }
                    $passWechat = rep()->wechat->m()
                        ->where('check_status', Wechat::STATUS_PASS)
                        ->where('user_id', $userId)
                        ->where('done_at', '!=', 0)
                        ->lockForUpdate()
                        ->get();
                    if (count($passWechat)) {
                        $lastWechat = $passWechat->sortByDesc('id')->first();
                        $userMember = rep()->member->getUserValidMember($userId);
                        if ($userMember && ((time() - $lastWechat->done_at) < User::DETAIL_MEMBER_CHANGE_TIME)) {
                            return ResultReturn::failed(trans('messages.charm_vip_modify_wechat_limit'));
                        } elseif (!$userMember && (time() - $lastWechat->done_at) < User::DETAIL_CHANGE_TIME) {
                            return ResultReturn::failed(trans('messages.modify_wechat_limit'));
                        }
                    }
                    $sendMessage = trans('messages.submitted_wechat_review_notice');
                    pocket()->common->sendNimMsgQueueMoreByPocketJob(pocket()->netease, 'msgSendMsg',
                        [config('custom.little_helper_uuid'), $user->uuid, $sendMessage]);
                    break;
            }

            $wechatData = [
                'user_id'      => $userId,
                'wechat'       => $request->post('wechat'),
                'qr_code'      => $request->post('qr_code'),
                'check_status' => Wechat::STATUS_DELAY
            ];

            if (count($createData) > 0) {
                $reviewId = rep()->userReview->m()->create($createData)->id;
                pocket()->common->commonQueueMoreByPocketJob(
                    pocket()->account,
                    'checkFaceGreen',
                    [$reviewId]
                );
            }
            $wechat = rep()->wechat->m()->create($wechatData);
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->wechat,
                'postParseWeChat',
                [$wechat]
            );

            return ResultReturn::success([]);
        });
        if ($result->getStatus() == false) {
            return api_rr()->forbidCommon($result->getMessage());
        }

        return api_rr()->postOK([]);
    }

    /**
     * 获取审核状态
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus()
    {
        $userId  = $this->getAuthUserId();
        $message = trans('messages.submitted_review_notice');
        [$status, $reason] = pocket()->user->getUserCheckStatus($userId);
        if ($status == 3) {
            if (user_agent()->os == 'ios') {
                return api_rr()->getOK(['status' => 3, 'alert_status' => false, 'message' => '']);
            }

            return api_rr()->forbidCommon(trans('messages.not_submit_review_info'));
        } elseif ($status == 2) {
            $message = $reason;
        }

        $isFollowWechat = rep()->userFollowOffice->m()->where('user_id', $userId)->where('status',
            UserFollowOffice::STATUS_FOLLOW)->first();

        return api_rr()->getOK([
            'status'           => $status,
            'message'          => $message,
            'is_follow_wechat' => (bool)$isFollowWechat
        ]);
    }

    /**
     * 完成认证弹窗
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function finishAlert()
    {
        $userId     = $this->getAuthUserId();
        $userReview = rep()->userReview->m()->where('user_id', $userId)
            ->where('check_status', UserReview::CHECK_STATUS_PASS)
            ->orderByDesc('id')->first();
        if (!$userReview) {
            return api_rr()->forbidCommon(trans('messages.not_pass_charm'));
        }
        $userReview->update(['alert_status' => UserReview::ALERT_STATUS_ACTIVE]);

        return api_rr()->postOK([]);
    }

    /**
     * 通过认证结果设置角色
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function setAuthUser(Request $request)
    {
        $userId     = $this->getAuthUserId();
        $faceRecord = rep()->faceRecord->m()->where('user_id', $userId)->first();
        $token      = $request->post('token');
        if (!$faceRecord || $token != $faceRecord->token) {
            return api_rr()->forbidCommon(trans('messages.curr_field_invalid_error',
                ['field' => 'token']));
        }
        $user       = rep()->user->getById($userId);
        $userAvatar = rep()->resource->m()
            ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
            ->where('related_id', $userId)
            ->orderByDesc('id')
            ->first();
        if (!$userAvatar) {
            return api_rr()->notFoundResult(trans('messages.get_avatar_failed_error'));
        }
        $uuid     = $userAvatar->uuid;
        $faceAuth = pocket()->aliYun->getAuthResponse('result', $uuid);
        if ($faceAuth->getStatus() == false) {
            return api_rr()->serviceUnknownForbid(trans('messages.get_face_detect_res_failed_error'));
        }

        if ($faceAuth->getData()['VerifyStatus'] == 1) {
            $result = pocket()->account->setUserAuth($user, $faceAuth->getData()['Material']['FaceImageUrl']);
            if ($result->getStatus() == false) {
                return api_rr()->forbidCommon($result->getMessage());
            }
        } else {
            return api_rr()->forbidCommon(trans('messages.not_pass_face_detect'));
        }

        return api_rr()->postOK([]);
    }

    /**
     * 根据新版只能核身设置角色
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setAuthUserV2(Request $request)
    {
        $userId     = $this->getAuthUserId();
        $faceRecord = rep()->faceRecord->m()->where('user_id', $userId)->first();
        $token      = $request->post('certify_id');
        if (!$faceRecord || $token != $faceRecord->token) {
            return api_rr()->forbidCommon(trans('messages.curr_field_invalid_error',
                ['field' => 'certify_id']));
        }
        $user     = rep()->user->getById($userId);
        $faceAuth = pocket()->aliYun->smartAuthResult($token);
        if ($faceAuth->getStatus() == false) {
            return api_rr()->serviceUnknownForbid(trans('messages.get_face_detect_res_failed_error'));
        }

        $data = $faceAuth->getData();
        if ($data['ResultObject']['Passed'] == 'T') {
            $metaInfo = json_decode($data['ResultObject']['MaterialInfo']);
            $result   = pocket()->account->setUserAuth($user, $metaInfo->facePictureInfo->pictureUrl);
            if ($result->getStatus() == false) {
                return api_rr()->forbidCommon($result->getMessage());
            }
        } else {
            return api_rr()->forbidCommon(trans('messages.not_pass_face_detect'));
        }

        return api_rr()->postOK([]);
    }

    /**
     * 上报自己的经纬度
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadLocation(Request $request)
    {
        $lng    = $request->post('lng', 0);
        $lat    = $request->post('lat', 0);
        $userId = $this->getAuthUserId();
        pocket()->common->commonQueueMoreByPocketJob(pocket()->account,
            'updateLocation', [$userId, $lng, $lat]);
        $updateEs = (new UpdateUserLocationToEsJob($userId, $lng, $lat))
            ->onQueue('update_user_location_to_es');
        dispatch($updateEs);
        $cityName = pocket()->userDetail->getCityByLoc($lng, $lat);
        if ($cityName) {
            rep()->userDetail->m()->where('user_id', $userId)->update(['region' => $cityName]);
        }

        return api_rr()->postOK([]);
    }

    /**
     * 锁微信
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function lockWechat()
    {
        $user           = rep()->user->getQuery()->select('id', 'uuid', 'gender')
            ->find($this->getAuthUserId());
        $userLock       = rep()->switchModel->m()->where('key', 'lock_wechat')->first();
        $userLockSwitch = rep()->userSwitch->m()
            ->where('user_id', $user->id)
            ->where('switch_id', $userLock->id)
            ->first();
        $updateStatus   = $userLockSwitch ? !$userLockSwitch->status : !$userLock->default_status;

        if ($userLockSwitch) {
            $userLockSwitch->update(['status' => $updateStatus]);
        } else {
            $userSwitchData = [
                'uuid'      => pocket()->util->getSnowflakeId(),
                'user_id'   => $user->id,
                'switch_id' => $userLock->id,
                'status'    => $updateStatus
            ];
            rep()->userSwitch->m()->create($userSwitchData);
        }

        if (optional($user)->gender == User::GENDER_WOMEN
            && pocket()->coldStartUser->isColdStartUser($user->id)) {
            pocket()->common->clodStartSyncDataByPocketJob(pocket()->coldStartUser,
                'updateColdStartUserSwitches',
                [$user, [SwitchModel::KEY_LOCK_WECHAT => $updateStatus]]);
        }


        return api_rr()->postOK([]);
    }

    /**
     * 通讯录屏蔽开关
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mobileShield(Request $request)
    {
        $status         = $request->post('status');
        $userId         = $this->getAuthUserId();
        $userLock       = rep()->switchModel->m()->where('key', 'phone')->first();
        $userLockSwitch = rep()->userSwitch->m()
            ->where('user_id', $userId)
            ->where('switch_id', $userLock->id)
            ->first();
        if ($userLockSwitch) {
            $userLockSwitch->update([
                'status' => $status
            ]);
        } else {
            $userSwitchData = [
                'uuid'      => pocket()->util->getSnowflakeId(),
                'user_id'   => $userId,
                'switch_id' => $userLock->id,
                'status'    => $status
            ];
            rep()->userSwitch->m()->create($userSwitchData);
        }

        return api_rr()->postOK([]);
    }

    /**
     * 修改资源状态（阅后即焚/红包视频）
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePhotoStatus(Request $request, int $uuid)
    {
        $type         = $request->post('type');
        $userId       = $this->getAuthUserId();
        $userResource = rep()->resource->m()
            ->where('uuid', $uuid)
            ->where('related_id', $userId)
            ->first();
        if (!$userResource) {
            return api_rr()->forbidCommon(trans('messages.modify_others_img_error'));
        }
        DB::beginTransaction();
        $userPhotoData = [
            'user_id'      => $userId,
            'resource_id'  => $userResource->id,
            'related_type' => UserPhoto::EXTENSION_MAPPING[$type],
            'amount'       => UserPhoto::AMOUNT_MAPPING[UserPhoto::EXTENSION_MAPPING[$type]],
            'status'       => UserPhoto::STATUS_OPEN
        ];
        switch ($type) {
            case UserPhoto::RELATED_TYPE_FREE_STR:
                rep()->userPhoto->m()
                    ->where('user_id', $userId)
                    ->where('resource_id', $userResource->id)
                    ->update([
                        'related_type' => UserPhoto::RELATED_TYPE_FREE,
                        'amount'       => UserPhoto::AMOUNT_MAPPING[UserPhoto::EXTENSION_MAPPING[$type]]
                    ]);
                break;
            case UserPhoto::RELATED_TYPE_RED_PACKET_STR:
            case UserPhoto::RELATED_TYPE_FIRE_STR:
                rep()->userPhoto->m()
                    ->where('user_id', $userId)
                    ->where('resource_id', $userResource->id)
                    ->lockForUpdate()
                    ->update([
                        'related_type' => UserPhoto::EXTENSION_MAPPING[$type],
                        'amount'       => UserPhoto::AMOUNT_MAPPING[UserPhoto::EXTENSION_MAPPING[$type]]
                    ]);
                break;
        }
        rep()->userPhotoChangeLog->m()->create($userPhotoData);
        DB::commit();
        $resource = rep()->resource->m()
            ->where('uuid', $uuid)
            ->first();
        if (!$resource) {
            return api_rr()->notFoundResult('当前资源已经不存在');
        }
        $cover   = '';
        $payType = 'free';
        if ($userPhotoData['related_type'] == UserPhoto::RELATED_TYPE_FIRE || $userPhotoData['related_type'] == UserPhoto::RELATED_TYPE_RED_PACKET) {
            if ($resource->type == 'image') {
                $cover      = $resource->preview . '?imageMogr2/blur/10x10';
                $smallCover = $resource->small_cover . '/blur/200x50';
                $payType    = 'fire';
            }
            if ($resource->type == 'video') {
                $cover      = $resource->preview . '?vframe/png/offset/0|imageMogr2/blur/10x10';
                $smallCover = $resource->small_cover . '|imageMogr2/blur/200x50';
                $payType    = 'red_packet';
            }
        } else {
            if ($resource->type == 'image') {
                $cover = $resource->preview;
            }
            if ($resource->type == 'video') {
                $cover = $resource->preview . '?vframe/png/offset/0';
            }
            $smallCover = $resource->small_cover;
        }
        $resource->setAttribute('cover', $cover);
        $resource->setAttribute('pay_type', $payType);
        $resource->setAttribute('small_cover', $smallCover);

        return api_rr()->postOK($resource);
    }

    /**
     * 做一些操作之前的前置操作
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function want(Request $request, int $uuid)
    {
        $type        = $request->get('type', 'all');
        $userId      = $this->getAuthUserId();
        $user        = rep()->user->getById($userId);
        $isCharmGirl = in_array(Role::KEY_CHARM_GIRL, explode(',', $user->role));
        $isMember    = rep()->member->getUserValidMember($userId);
        $wantArr     = [];
        switch ($type) {
            case 'all':
                $wantArr['moment']     = pocket()->account->getMomentWant($user, $isCharmGirl, $isMember);
                $wantArr['stealth']    = ($isCharmGirl && $isMember);
                $wantArr['chat']       = pocket()->account->getChatWant($user, $uuid);
                $wantArr['update']     = pocket()->account->getUpdateWant($user);
                $wantArr['charm_auth'] = pocket()->account->getCharmAuthWant($user);
                break;
            case 'moment':
                $reviewing = pocket()->account->checkUserReviewing($user);
                if ($reviewing->getStatus() == false) {
                    return api_rr()->forbidCommon(trans('messages.review_not_release_moment'));
                }
                $wantArr['moment'] = pocket()->account->getMomentWant($user, $isCharmGirl, $isMember);
                break;
            case 'stealth':
                $wantArr['stealth'] = ($isCharmGirl && $isMember);
                break;
            case 'chat':
                $wantArr['chat'] = pocket()->account->getChatWant($user, $uuid);
                break;
            case 'update':
                if (version_compare(user_agent()->clientVersion, '2.4.3', '>=')) {
                    $wantArr['update'] = pocket()->account->getUpdateWantV2($user);
                } else {
                    $wantArr['update'] = pocket()->account->getUpdateWant($user);
                }
                break;
            case 'charm_auth':
                $wantArr['charm_auth'] = pocket()->account->getCharmAuthWant($user);
                break;
            default:
                return api_rr()->forbidCommon(trans('messages.illegal_params_error'));
        }

        return api_rr()->getOK($wantArr);
    }

    /**
     * 注销账号
     *
     * @param  int  $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $uuid)
    {
        $user = $this->getAuthUser();
        if (!$user || rep()->user->getById($user->id)->destroy_at > 0) {
            return api_rr()->notFoundUser();
        }
        $hasDestroy = rep()->userDestroy->m()->where('user_id', $user->id)
            ->where('destroy_at', '>=', 0)
            ->where('cancel_at', 0)
            ->first();
        if ($hasDestroy) {
            return api_rr()->forbidCommon(trans('messages.repeat_apply_cancel_account'));
        }
        $destroyArr = [
            'user_id'    => $user->id,
            'destroy_at' => time() + (int)config('custom.user_destroy_time'),
        ];
        if (!rep()->userDestroy->m()->create($destroyArr)) {
            return api_rr()->forbidCommon(trans('messages.cancel_account_failed_error'));
        }
        //申请注销后隐身
        pocket()->account->hideUser($user->id);

        return api_rr()->postOK((object)[]);
    }

    /**
     * 激活账号
     *
     * @param  int  $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate(int $uuid)
    {
        $user = $this->getAuthUser();
        if (!$user || rep()->user->getById($user->id)->destroy_at > 0) {
            return api_rr()->notFoundUser();
        }
        $hasDestroy = rep()->userDestroy->m()->where('user_id', $user->id)
            ->where('destroy_at', '>=', 0)
            ->where('cancel_at', 0)
            ->first();
        if (!$hasDestroy) {
            return api_rr()->forbidCommon(trans('messages.pls_apply_cancel_account'));
        }
        $cancel = rep()->userDestroy->m()->where('user_id', $user->id)
            ->where('destroy_at', '>=', 0)
            ->where('cancel_at', 0)
            ->update(['cancel_at' => time()]);
        if (!$cancel) {
            return api_rr()->forbidCommon(trans('messages.pls_apply_cancel_account'));
        }
        pocket()->account->showUser($user->id);

        return api_rr()->postOK((object)[], trans('messages.account_activate'));
    }

    /**
     * 公众号是否关注
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function followOfState(Request $request, int $uuid)
    {
        $userId = $this->getAuthUserId();
        $resp   = pocket()->userFollowOffice->getWeChatOfficeFollowArr($userId);
        if (!$resp->getStatus()) {
            return api_rr()->forbidCommon(trans('messages.generate_qrcode_notice'),
                ['url' => '', 'is_follow' => false, 'push_msg_switch' => false,]);
        }
        $officeData = $resp->getData();
        if (!$officeData['is_follow']) {
            return api_rr()->customFailed(
                trans('messages.validation_fails_focus_wechat_platform'),
                ApiBusinessCode::FOLLOW_OFFICE_FAILED, $officeData);
        }


        return api_rr()->getOK($officeData,
            trans('messages.success_focus_wechat_platform'));
    }

    /**
     * 谁看过我列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function visited(Request $request)
    {
        $userId = $this->getAuthUserId();
        $user   = rep()->user->getById($userId);
        $limit  = (int)$request->get('limit', 10);
        $page   = $request->get('page');
        $list   = rep()->userVisit->m()
            ->where('related_id', $userId)
            ->where('related_type', UserVisit::RELATED_TYPE_INTRODUCTION)
            ->when($page, function ($query) use ($page) {
                $query->where('visit_time', '<', $page);
            })
            ->orderByDesc('visit_time')
            ->limit($limit)
            ->get();
        if (count($list) == 0) {
            return api_rr()->notFoundResult();
        }
        $users = rep()->user->m()->whereIn('id', $list->pluck('user_id')->toArray())->get();
        pocket()->user->appendToUsers($users,
            [
                'avatar',
                'distance' => $user,
            ]);
        $isVip     = rep()->member->getUserValidMember($userId);
        $listUsers = [];
        foreach ($users as $user) {
            if (!$isVip) {
                $user->setAttribute('avatar', $user->avatar . '?imageMogr2/blur/100x100');
            }
            $listUsers[$user->id] = $user;
        }
        $result = [];
        foreach ($list as $item) {
            $result[] = $listUsers[$item->user_id];
        }

        $last = $list->last()->visit_time;

        return api_rr()->getOK(pocket()->util->getPaginateFinalData($result, $last));
    }

    /**
     * 打开app时显示的弹窗
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function popup(Request $request)
    {
        $user                 = rep()->user->getQuery()->find($request->user()->id);
        $response             = ['active' => false, 'lock_wechat' => false];
        $lockWechatSwitch     = rep()->switchModel->m()->where('key', SwitchModel::KEY_LOCK_WECHAT)->first();
        $userLockWechatSwitch = rep()->userSwitch->m()->where('user_id', $user->id)->where('switch_id',
            $lockWechatSwitch->id)->first();

        if ($userLockWechatSwitch && $userLockWechatSwitch->status == UserSwitch::STATUS_ADMIN_LOCK) {
            $response['lock_wechat'] = true;
            pocket()->netease->sendSystemMessage($user->uuid,
                trans('messages.forcibly_required_modify_wehat_account'));
        }
        $userMongo = mongodb('user_mark')->where('_id', $user->id)->first();
        if ($userMongo && (key_exists('marks', $userMongo) && key_exists('visit',
                    $userMongo['marks']) && $userMongo['marks']['visit'] == true)) {
            $response['active'] = true;
            pocket()->netease->sendSystemMessage($user->uuid, trans('messages.not_active_stealth_recovery_notice'));
            mongodb('user_mark')->where('_id', $user->id)->update(['marks.visit' => false]);
        }

        $reviewCheck              = rep()->userReview->m()->where('user_id', $user->id)->where('check_status',
            UserReview::CHECK_STATUS_PASS)->orderByDesc('id')->first();
        $response['charm_script'] = $reviewCheck && $reviewCheck->alert_status == 0;
        if ($user->gender == User::GENDER_MAN) {
            $manInviteResp = pocket()->user->manInvitePopup($user);
            if ($manInviteResp->getStatus()) {
                $response['invite'] = $manInviteResp->getData();
            }
        }
        $jumpUrlResp = pocket()->user->getJumpUrlPopup($user);
        $jumpUrlResp->getStatus() && $response['jump_url'] = $jumpUrlResp->getData();

        return api_rr()->getOK($response);
    }

    /**
     * 更改是否微信推送消息开关
     *
     * @param  SwitchTmpMsgRequest  $request
     * @param  int                  $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function switchTmpMsg(SwitchTmpMsgRequest $request, int $uuid)
    {
        $status = $request->post('status');
        $userId = $this->getAuthUserId();
        pocket()->userFollowOffice->getWeChatOfficeFollowArr($userId);
        pocket()->userSwitch->postPushTemMsgState($userId, (bool)$status);
        $weChatOfficeFollowResp = pocket()->userFollowOffice->getWeChatOfficeFollowArr($userId);

        return api_rr()->postOK($weChatOfficeFollowResp->getData());
    }

    /**
     * 更新活跃时间
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateActiveTime(Request $request)
    {
        $userId     = $this->getAuthUserId();
        $now        = time();
        $os         = user_agent()->os;
        $runVersion = user_agent()->clientVersion;
        $language   = $request->header('request-cu-language');

        if ($isUpdateActiveAt = pocket()->user->whetherUpdateUserActiveAt($userId, $now)) {
            $updateUserActiveAt = (new UpdateUserActiveAtJob($userId, $now, $os, $runVersion, $language))
                ->onQueue('update_user_active_at');
            dispatch($updateUserActiveAt);

            $updateUserField = (new UpdateUserFieldToEsJob($userId, ['active_at' => $now]))
                ->onQueue('update_user_field_to_es');
            dispatch($updateUserField);
        }

        return api_rr()->postOK([]);
    }

    /**
     * 获得解锁退还
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refundLocked(Request $request, int $uuid)
    {
        $userId             = $this->getAuthUserId();
        $refundLockedAmount = pocket()->mongodb->getRefundLockedAmount($userId);
        $finalAmount        = (int)($refundLockedAmount / 10);

        return api_rr()->getOK([
            'amount' => $finalAmount,
            'mark'   => sprintf(trans('messages.refund_diamonds_tmpl'), $finalAmount),
        ]);
    }

    /**
     * 解锁退还已读
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refundLockedRead(Request $request, int $uuid)
    {
        $userId = $this->getAuthUserId();
        mongodb('user')->where('_id', $userId)->update(['mark.refund_locked_amount' => 0]);

        $refundLockedAmount = pocket()->mongodb->getRefundLockedAmount($userId);

        return api_rr()->postOK([
            'amount' => $refundLockedAmount / 100,
            'mark'   => sprintf(trans('messages.refund_diamonds_tmpl'),
                $refundLockedAmount / 100),
        ]);
    }

    /**
     * 获取会员运营埋点信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGIOMemberOperation()
    {
        $userId          = $this->getAuthUserId();
        $result          = ['member_operation' => []];
        $user            = rep()->user->getById($userId);
        $userDetail      = rep()->userDetail->m()->where('user_id', $userId)->first();
        $userDetailExtra = rep()->userDetailExtra->m()->where('user_id', $userId)->first();
        $inviteRecord    = rep()->inviteRecord->m()
            ->where('target_user_id', $userId)
            ->where('status', InviteRecord::STATUS_SUCCEED)
            ->first();
        $member          = rep()->member->getUserValidMember($userId);
        $hobbys          = rep()->tag->m()->where('type', Tag::TYPE_HOBBY)->get();
        $userTags        = rep()->userTag->m()->where('user_id', $userId)->whereIn('tag_id',
            $hobbys->pluck('id')->toArray())->get();
        $userJob         = rep()->userJob->m()->where('user_id', $userId)->first();
        if ($user) {
            $result['member_operation']['sex_ppl'] = $user->gender == 2 ? "女" : "男";
            $result['member_operation']['age_ppl'] = $user->age;
        }
        $result['member_operation']['province_ppl'] = '';
        $result['member_operation']['city_ppl']     = '';
        if ($userDetail) {
            $result['member_operation']['channel_ppl'] = $userDetail->channel;
            $city                                      = rep()->area->m()->where('name', $userDetail->region)->first();
            if ($city) {
                $province = rep()->area->m()->where('id', $city->pid)->first();
                if ($province) {
                    $result['member_operation']['province_ppl'] = $province->name;
                    $result['member_operation']['city_ppl']     = $city->name;
                } else {
                    $result['member_operation']['province_ppl'] = $city->name;
                    $result['member_operation']['city_ppl']     = $city->name;
                }
            }
        }
        if ($userDetailExtra) {
            $tags = rep()->tag->m()->whereIn('id', [
                $userDetailExtra->emotion,
                $userDetailExtra->child,
                $userDetailExtra->education,
                $userDetailExtra->income,
                $userDetailExtra->figure,
                $userDetailExtra->smoke,
                $userDetailExtra->drink,
            ])->get();
            foreach ($tags as $tag) {
                switch ($tag->type) {
                    case Tag::TYPE_EMOTION:
                        $result['member_operation']['relationshi_ppl'] = $tag->name;
                        break;
                    case Tag::TYPE_CHILD:
                        $result['member_operation']['ifHaveKids_ppl'] = $tag->name;
                        break;
                    case Tag::TYPE_EDUCATION:
                        $result['member_operation']['education_ppl'] = $tag->name;
                        break;
                    case Tag::TYPE_INCOME:
                        $result['member_operation']['annualIncome_ppl'] = $tag->name;
                        break;
                    case Tag::TYPE_FIGURE:
                        $result['member_operation']['stature_ppl'] = $tag->name;
                        break;
                    case Tag::TYPE_SMOKE:
                        $result['member_operation']['ifSmoking_ppl'] = $tag->name;
                        break;
                    case Tag::TYPE_DRINK:
                        $result['member_operation']['ifDrink_ppl'] = $tag->name;
                        break;
                }
            }
        }
        if (count($userTags) > 0) {
            $hobbyData = [];
            foreach ($userTags as $userTag) {
                $hobbyData[] = $hobbys->where('id', $userTag->tag_id)->first()->name;
            }
            $result['member_operation']['hobby_ppl'] = implode(',', $hobbyData);
        }
        if ($inviteRecord) {
            $result['member_operation']['inviteUserID_evar']  = $inviteRecord->user_id;
            $inviteUser                                       = rep()->userDetail->m()->where('user_id',
                $inviteRecord->user_id)->first();
            $result['member_operation']['InvitationCode_ppl'] = $inviteUser->invite_code;
        }
        if ($member) {
            $result['member_operation']['memberType_ppl']    = true;
            $card                                            = rep()->card->m()->where('id', $member->card_id)->first();
            $result['member_operation']['VIPmemberType_ppl'] = $card->name;
        }
        if ($userJob) {
            $job                                          = rep()->job->m()->where('id', $userJob->job_id)->first();
            $result['member_operation']['profession_ppl'] = $job->name;
        }

        return api_rr()->getOK($result);
    }
}

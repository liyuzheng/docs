<?php


namespace App\Pockets;

use App\Models\InviteRecord;
use App\Models\Tag;
use App\Models\User;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\UserAb;
use App\Models\UserPhoto;
use App\Models\UserPowder;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Role;
use App\Models\UserRelation;
use App\Foundation\Services\Guzzle\GuzzleHandle;
use GuzzleHttp\Exception\GuzzleException;
use App\Models\UserDetail;
use Illuminate\Support\Facades\DB;
use App\Models\UserReview;
use App\Models\UserResource;
use App\Models\UserAttrAudit;
use App\Models\Wechat;
use App\Constant\NeteaseCustomCode;
use Illuminate\Database\Eloquent\Model;
use App\Models\Resource;
use App\Models\ResourceCheck;
use App\Jobs\UpdateUserLocationToEsJob;
use App\Models\Moment;
use App\Models\SfaceRecord;
use App\Models\SwitchModel;
use App\Jobs\UpdateUserInfoToMongoJob;
use App\Models\FacePic;
use App\Models\Sms;

class AccountPocket extends BasePocket
{

    /**
     * 获取一个用户和另一个用户的互动权限
     *
     * @param  int  $userId
     * @param  int  $tUserId
     *
     * @return mixed
     */
    public function getInteractivePower(int $userId, int $tUserId)
    {
        $startTime            = strtotime(date('Y-m-d', time()));
        $endTime              = $startTime + 86400;
        $userRelation         = rep()->userRelation->m()
            ->where('user_id', $userId)
            ->where('target_user_id', $tUserId)
            ->where(function ($query) {
                $query->where('expired_at', 0)->orWhere('expired_at', '>', time());
            })
            ->get()->pluck('type')->toArray();
        $powerList['is_chat'] = in_array(UserRelation::TYPE_PRIVATE_CHAT, $userRelation) ? true : false;
        if (in_array(UserRelation::TYPE_LOOK_WECHAT, $userRelation) || $userId == $tUserId) {
            $powerList['is_show_wechat'] = true;
        } else {
            $powerList['is_show_wechat'] = false;
        }
        $powerList['can_unlock'] = false;

        if (pocket()->member->userIsMember($userId)) {
            $dailyUnlockCount = rep()->userRelation->m()
                ->select('target_user_id')
                ->where('user_id', $userId)
                ->where('target_user_id', $tUserId)
                ->whereBetween('created_at', [$startTime, $endTime])
                ->groupBy('target_user_id')
                ->get();
            if (count($dailyUnlockCount) < UserRelation::VIP_FREE_UNLOCK_USER_COUNT) {
                $powerList['can_unlock'] = true;
            }
        }

        return $powerList;
    }

    /**
     * 通过手机号获得用户基本信息
     *
     * @param  string|int  $certificate
     * @param  string      $filed
     *
     * @return ResultReturn
     */
    public function getLatestAccountInfoByFiled($certificate, $filed, $clientVersion)
    {
        $user = rep()->user->getLatestUserByFiled($certificate, $filed, ['id']);
        if (!$user) {
            return ResultReturn::failed('找不到用户');
        }

        return $this->getAccountInfo($user->id, $clientVersion);
    }

    /**
     * 获取用户完整信息
     *
     * @param  int  $userId
     *
     * @return ResultReturn
     */
    public function getAccountInfo(int $userId, $clientVersion)
    {
        $tags = rep()->tag->m()->where('type', '>=', Tag::TYPE_EMOTION)->get();
        $user = rep()->user->m()->select('id', 'uuid', 'number', 'nickname', 'gender',
            'birthday', 'role', 'hide')->with([
            'userDetail'      => function ($query) {
                $query->select(['user_id', 'intro', 'region', 'height', 'weight', 'reg_schedule']);
            },
            'userDetailExtra' => function ($query) {
                $query->select(['user_id', 'emotion', 'child', 'education', 'income', 'figure', 'smoke', 'drink']);
            }
        ])->where('id', $userId)->first();
        if (!$user) {
            return ResultReturn::failed(trans('messages.user_not_exist'));
        }

        $appendData = [
            'member',
            'auth_user',
            'charm_girl',
            'netease' => ['accid', 'token'],
            'job',
            'avatar',
            'wechat',
            'hobby'
        ];

        $this->appendTagToUserByType($user, Tag::TYPE_TAG_MAN);
        if (version_compare(user_agent()->clientVersion, '1.6.0', '>=')) {
            $appendData['album'] = $user;
        } else {
            $appendData['photo'] = $user;
        }
        pocket()->user->appendToUser($user, $appendData);
        $this->appendFollowsToUser($user);
        $this->appendUserWithdrawUrlToUser($user);
        $this->appendToQaToUser($user, $clientVersion);
        $this->appendStatusToUser($user);
        pocket()->userFollowOffice->appendFollowOfArr($user);
        $this->appendInviteInfoToUser($user);
        $userDetailExtra = $user->userDetailExtra->toArray();
        foreach ($userDetailExtra as $tagKey => $tagId) {
            if (!$tagId) {
                $user->userDetailExtra->$tagKey = null;
            }
            $user->userDetailExtra->$tagKey = $tags->where('id', $tagId)->first() ? $tags->where('id',
                $tagId)->first()->toArray() : null;
        }

        return ResultReturn::success($user);
    }

    /**
     * 给User模型增加job属性
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendJobToUser(User $user)
    {
        $jobsId = rep()->userJob->m()->where('user_id', $user->id)->value('job_id');
        if ($jobsId) {
            $jobs = rep()->job->getById($jobsId, ['uuid', 'name'])->toArray();
            $user->setAttribute('job', $jobs);
        } else {
            $user->setAttribute('job', (object)[]);
        }

        return $user;
    }

    /**
     * 给Users模型增加job属性
     *
     * @param  Collection  $users
     *
     * @return Collection
     */
    public function appendJobToUsers(Collection $users)
    {
        $usersId = $users->pluck('id')->toArray();
        $midJobs = rep()->userJob->m()
            ->select(['user_id', 'job_id'])
            ->whereIn('user_id', $usersId)
            ->get();
        $jobsId  = $midJobs->pluck('job_id')->toArray();
        $jobs    = rep()->job->getByIds($jobsId, ['id', 'uuid', 'name']);
        foreach ($users as $user) {
            $job = $jobs->whereIn('id', $midJobs->where('user_id', $user->id)
                ->pluck('job_id')->toArray())->first();
            $user->setAttribute(
                'job',
                $job ?? (object)[]
            );
        }

        return $users;
    }

    /**
     * 给User模型增加type属性
     *
     * @param  User  $user
     * @param        $type
     *
     * @return User
     */
    public function appendTagToUserByType(User $user, $type)
    {
        $tagsId = rep()->userTag->m()->where('user_id', $user->id)->pluck('tag_id')->toArray();
        $tags   = rep()->tag->m()->where('type', $type)->select(['uuid', 'name'])->whereIn('id',
            $tagsId)->get()->toArray();
        $user->setAttribute('tags', $tags);

        return $user;
    }

    /**
     * 根据type给Users模型增加tag属性
     *
     * @param  Collection  $users
     * @param              $type
     *
     * @return Collection
     */
    public function appendTagToUsersByType(Collection $users, $type)
    {
        $usersId = $users->pluck('id')->toArray();
        $midTags = rep()->userTag->m()->whereIn('user_id', $usersId)->get();
        $tagsId  = $midTags->pluck('tag_id')->toArray();
        $tags    = rep()->tag->m()->where('type', $type)->select(['id', 'uuid', 'name'])->whereIn('id',
            $tagsId)->get();
        foreach ($users as $user) {
            $user->setAttribute('tag',
                $tags->whereIn('id', $midTags->where('user_id', $user->id)
                    ->pluck('tag_id')->toArray())
            );
        }

        return $users;
    }

    /**
     * 给User模型增加关注与被关注数
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendFollowsToUser(User $user)
    {
        $followCount   = rep()->userFollow->m()->where('user_id', $user->id)->count();
        $followedCount = rep()->userFollow->m()->where('follow_id', $user->id)->count();
        $user->setAttribute('follow_count', $followCount);
        $user->setAttribute('followed_count', $followedCount);

        return $user;
    }

    /**
     * 获得qa数组
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendToQaToUser(User $user, $version)
    {
        $sexField = [0 => '男', 1 => '男', 2 => '女'];
        if (version_compare(user_agent()->clientVersion, '2.4.0', '>')) {
            $url = 'https://xiaoquana-qa.sobot.com/chat/h5/v2/index.html?sysnum=fb002f887ae4490080d4293e5baa271b&source=2&partnerid=' .
                $user->uuid . '&uname=' . urlencode($user->nickname) .
                '&customer_fields=' . urlencode(json_encode(['customField2' => $sexField[$user->gender]]));
        } else {
            $url = 'https://xiaoquana-qa.sobot.com/chat/h5/v2/index.html?sysnum=fb002f887ae4490080d4293e5baa271b&source=2&partnerid=' .
                $user->uuid . '&uname=' . $user->nickname;
        }
        $qaArr = [
            'url' => $url
        ];
        $user->setAttribute('qa', $qaArr);

        return $user;
    }

    /**
     * 获得账户状态
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendStatusToUser(User $user)
    {
        $resp   = pocket()->userDestroy->getUserDestroyState($user->id);
        $status = $resp->getData()['state'];
        $user->setAttribute('account_state', $status);

        return $user;
    }

    /**
     * 获得邀请数据
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendInviteInfoToUser(User $user)
    {
        $user->setAttribute('invite_info', [
            'invite_qrcode_url' => 'http://i.xiaoquann.com/invite_slb?uuid=' . $user->uuid
        ]);

        return $user;
    }

    /**
     * 账户是否被注销
     *
     * @param  int  $userId
     *
     * @return bool
     */
    public function whetherDestroyUser(int $userId)
    {
        $resp   = pocket()->userDestroy->getUserDestroyState($userId);
        $status = $resp->getData()['state'];
        if (in_array($status, ['destroyed'])) {
            return true;
        }

        return false;
    }

    /**
     * 给用户基础数据中append云信信息
     *
     * @param  User   $user
     * @param  array  $fields
     *
     * @return User
     */
    public function appendNeteaseInfoToUser(User $user, array $fields = [])
    {
        $fillData = [];
        $fields   = $fields ? $fields : ['accid', 'token'];
        foreach ($fields as $field) {
            switch ($field) {
                case 'accid':
                    $value = (string)$user->uuid;
                    break;
                case 'token':
                    $value = pocket()->userAuth->getNeteaseTokenByUserId($user->id);
                    break;
                default:
                    $value = '';
                    break;
            }
            in_array($field, $fields) && $fillData[$field] = $value;
        }
        $user->setAttribute('netease', $fillData);

        return $user;
    }


    /**
     * 给一组用户基础数据中添加云信消息
     *
     * @param  Collection  $users
     * @param  array       $fields
     *
     * @return Collection
     */
    public function appendNeteaseInfoToUsers(Collection $users, array $fields = [])
    {
        $fillData = [];
        $fields   = $fields ? $fields : ['accid', 'token'];
        $values   = pocket()->userAuth->getNeteaseTokenByUserIds($users->pluck('id')->toArray());
        /** @var User $user */
        foreach ($users as $user) {
            foreach ($fields as $field) {
                switch ($field) {
                    case 'accid':
                        $value = (string)$user->uuid;
                        break;
                    case 'token':
                        $value = $values[$user->id] ?? "";
                        break;
                    default:
                        $value = '';
                        break;
                }
                in_array($field, $fields) && $fillData[$field] = $value;
            }
            $user->setAttribute('netease', $fillData);
        }

        return $users;
    }

    /**
     * 获取图片详情
     *
     * @param  array  $images
     *
     * @return ResultReturn
     */
    public function getImagesDetail(array $images)
    {
        $filePath = config('custom.file.url');
        $client   = (new GuzzleHandle)->getClient();
        $path     = sprintf("%s/file?paths=%s", $filePath, implode(',', $images));
        try {
            $httpRes   = $client->get($path)->getBody()->getContents();
            $picDetail = json_decode($httpRes, true);
            $data      = $picDetail['data'] ?? [];
            $result    = [];
            foreach ($images as $image) {
                $result[$image] = [
                    'height' => $data[$image]['height'] ?? 0,
                    'width'  => $data[$image]['width'] ?? 0,
                ];
            }

            return ResultReturn::success($result);
        } catch (GuzzleException $e) {
            return ResultReturn::failed(
                trans('messages.get_img_info_failed_error'));
        }
    }

    /**
     * 普通用户更新
     *
     * @param $userDetail
     * @param $user
     * @param $userDetailField
     * @param $userDetailExtraField
     * @param $reqPost
     *
     * @return ResultReturn
     * @throws \Exception
     */
    public function simpleUpdate($userDetail, $user, $userDetailField, $userDetailExtraField, $reqPost)
    {
        $now = time();
        //更新邀请码
        if (isset($reqPost['invite_code']) && $reqPost['invite_code']
            && (version_compare($userDetail->reg_version, '2.2.0', '>=')
                || $userDetail->reg_os != UserDetail::REG_OS_ANDROID)) {
            if (!$userDetail->inviter) {
                $inviterId = rep()->userDetail->getUserIdByInviteCode((int)$reqPost['invite_code']);
                if ($inviterId) {
                    $inviter = rep()->user->getQuery()->find($inviterId);
                    pocket()->inviteRecord->postBeInviterRegister($inviter, $user, InviteRecord::CHANNEL_APP);
                }
            }
        }
        if (isset($reqPost['gender'])) {
            if ($userDetail->reg_schedule == UserDetail::REG_SCHEDULE_GENDER) {
                DB::transaction(function () use ($user, $reqPost, $userDetail) {
                    $userDetail = rep()->userDetail->getQuery()->where('user_id', $user->id)->lockForUpdate()->first();
                    $user->update(['gender' => $reqPost['gender']]);
                    if ($reqPost['gender'] == User::GENDER_MAN) {
                        pocket()->common->commonQueueMoreByPocketJob(pocket()->user,
                            'addVisitedToUser', [$user->id], 60 * rand(2, 10));
                    } else {
                        $switch         = rep()->switchModel->getQuery()->where('key',
                            SwitchModel::KEY_CLOSE_WE_CHAT_TRADE)->first();
                        $userSwitchData = [
                            'user_id'   => $user->id,
                            'switch_id' => $switch->id,
                            'status'    => 1,
                            'uuid'      => pocket()->util->getSnowflakeId()
                        ];
                        rep()->userSwitch->getQuery()->create($userSwitchData);
                    }
                    $userDetail->update(['reg_schedule' => UserDetail::REG_SCHEDULE_BASIC]);
                });
            } else {
                return ResultReturn::failed(trans('messages.not_change_sex'));
            }
        }

        $basicMsg = [];
        if (isset($reqPost['birthday'])) {
            $basicMsg['birthday'] = strtotime($reqPost['birthday']);
        }
        if (isset($reqPost['nickname'])) {
            if (!$reqPost['nickname']) {
                return ResultReturn::failed(trans('messages.nickname_not_empty'));
            } elseif ($user->gender == User::GENDER_WOMEN) {
                $uu = rep()->user->getUserByNickName($reqPost['nickname']);
                if (count($uu) > 0 && $user->nickname != $reqPost['nickname']) {
                    return ResultReturn::failed(trans('messages.nickname_repeat_error'));
                }
            }
            $result = pocket()->neteaseDun->checkText(get_md5_random_str(), $reqPost['nickname'],
                config('netease.keys.dun.user_nickname'), $user->uuid);
            if ($result->getStatus() == false) {
                return ResultReturn::failed(trans('messages.nickname_check_error'));
            }
            $checkData   = $result->getData();
            $checkStatus = $checkData['check_status'];
            if ($checkStatus != 100) {
                return ResultReturn::failed(trans('messages.nickname_modify_error'));
            }
            $basicMsg['nickname'] = $reqPost['nickname'];
        }
        if (count($basicMsg) > 0) {
            DB::transaction(function () use ($userDetail, $basicMsg, $user) {
                if (key_exists('birthday',
                        $basicMsg) && ($basicMsg['birthday'] > 2147483647 || $basicMsg['birthday'] < -2147483647)) {
                    $basicMsg['birthday'] = 0;
                }
                $result = $user->update($basicMsg);
                if (isset($basicMsg['nickname'])) {
                    pocket()->netease->userUpdateUinfo($user->uuid, $basicMsg['nickname']);
                }
                if ($userDetail->reg_schedule == UserDetail::REG_SCHEDULE_BASIC && $result) {
                    $userDetail->update(['reg_schedule' => UserDetail::REG_SCHEDULE_FINISH]);
                    $message = trans('messages.welcome_to_circle');
                    if ($user->gender == 1) {
                        pocket()->common->sendNimMsgQueueMoreByPocketJob(
                            pocket()->netease, 'msgSendMsg',
                            [config('custom.little_helper_uuid'), $user->uuid, $message]
                        );
                        pocket()->common->commonQueueMoreByPocketJob(
                            pocket()->account,
                            'addUserFakeGreet',
                            [$user->id, 1]
                        );
                    }
                    pocket()->common->commonQueueMoreByPocketJob(
                        pocket()->account,
                        'reportUserRegisterFinishData',
                        [$user->id]
                    );
                }
            });
        }
        //        if (isset($reqPost['relation'])) {
        //            // -1代表客户端网络错误获取不到uuid,但是为了体验必须要过
        //            if ($reqPost['relation'] == -1) {
        //                $userDetailEloquent->update(['reg_schedule' => UserDetail::REG_SCHEDULE_FINISH]);
        //            } else {
        //                $tag = rep()->tag->getByUUID($reqPost['relation'], ['id']);
        //                if (!$tag) {
        //                    return ResultReturn::failed('获取tag失败，请重试');
        //                }
        //                DB::transaction(function () use ($user, $reqPost, $userDetail, $userDetailEloquent, $tag) {
        //                    $relationData = [
        //                        'uuid'    => pocket()->util->getSnowflakeId(),
        //                        'user_id' => $user->id,
        //                        'tag_id'  => $tag->id
        //                    ];
        //                    $result       = rep()->userTag->m()->create($relationData);
        //                    if ($userDetail->reg_schedule == UserDetail::REG_SCHEDULE_RELATION && $result) {
        //                        $userDetailEloquent->update(['reg_schedule' => UserDetail::REG_SCHEDULE_FINISH]);
        //                    }
        //                });
        //            }
        //            $message = '欢迎来到小圈，小圈是主打高端线下约会的平台，所有魅力女生资料都通过真人认证，保证真实有效，您可以放心使用～';
        //            if ($user->gender == 1) {
        //                pocket()->common->sendNimMsgQueueMoreByPocketJob(
        //                    pocket()->netease,
        //                    'msgSendMsg',
        //                    [config('custom.little_helper_uuid'), $user->uuid, $message]
        //                );
        //            }
        //        }

        if (isset($reqPost['job'])) {
            $job        = rep()->job->m()->where('uuid', $reqPost['job'])->first();
            $createData = [
                'uuid'    => pocket()->util->getSnowflakeId(),
                'user_id' => $user->id,
                'job_id'  => $job->id
            ];
            rep()->userJob->m()->where('user_id', $user->id)->delete();
            rep()->userJob->m()->create($createData);
        }

        $createData = [];
        foreach ($userDetailField as $item) {
            if (key_exists($item, $reqPost)) {
                if ($item == 'intro') {
                    $result = pocket()->neteaseDun->checkText(get_md5_random_str(), $reqPost['intro'],
                        config('netease.keys.dun.user_intro'), $user->uuid);
                    if ($result->getStatus() == false) {
                        return ResultReturn::failed(trans('messages.sign_fail'));
                    }
                    $checkData   = $result->getData();
                    $checkStatus = $checkData['check_status'];
                    if ($checkStatus != 100) {
                        return ResultReturn::failed(trans('messages.sign_modify_error'));
                    }
                }
                $createData[$item] = $reqPost[$item];
            }
        }
        $detailExtraData = [];
        $hobbyData       = [];
        foreach ($userDetailExtraField as $item) {
            if (key_exists($item, $reqPost)) {
                if ($item == 'hobby') {
                    $hobbys = [];
                    $tags   = rep()->tag->m()->whereIn('uuid', $reqPost[$item])->get();
                    foreach ($tags as $tag) {
                        $hobbys[$tag->uuid] = $tag->id;
                    }
                    foreach ($reqPost[$item] as $value) {
                        $hobbyData[] = [
                            'uuid'       => pocket()->util->getSnowflakeId(),
                            'user_id'    => $user->id,
                            'tag_id'     => $hobbys[$value],
                            'created_at' => $now,
                            'updated_at' => $now
                        ];
                    }
                } else {
                    $detailExtraData[$item] = $reqPost[$item];
                }
            }
        }
        $transDetailExtraData = pocket()->account->getDetailExtraUpdateArr($detailExtraData);
        //如果有邀请码,必须unset掉,否则会更新掉用户的邀请码
        if (isset($createData['invite_code'])) {
            unset($createData['invite_code']);
        }
        DB::transaction(function () use ($userDetail, $createData, $user, $transDetailExtraData, $hobbyData) {
            $userDetail->update($createData);
            rep()->userDetailExtra->m()->where('user_id', $user->id)->update($transDetailExtraData);
            if (count($hobbyData) > 0) {
                $hobbyTags = rep()->tag->m()->where('type', Tag::TYPE_HOBBY)->get()->pluck('id')->toArray();
                rep()->userTag->m()->where('user_id', $user->id)->whereIn('tag_id', $hobbyTags)->delete();
                rep()->userTag->m()->insert($hobbyData);
            }
        });

        return ResultReturn::success([]);
    }

    /**
     * 特殊用户修改信息
     *
     * @param $user
     * @param $userDetail
     * @param $reqPost
     *
     * @return ResultReturn
     * @throws \Exception
     */
    public function specialUpdate($user, $userDetail, $reqPost)
    {
        $message = '修改成功';
        $now     = time();

        $basicMsg = [];
        if (isset($reqPost['birthday'])) {
            $basicMsg['birthday'] = strtotime($reqPost['birthday']);
        }
        $user->update($basicMsg);

        if (isset($reqPost['job'])) {
            $job = rep()->job->m()->where('uuid', $reqPost['job'])->first();
            if (!$job) {
                return ResultReturn::failed(trans('messages.not_found_job'));
            }
            $jobCreateData = [
                'uuid'    => pocket()->util->getSnowflakeId(),
                'user_id' => $user->id,
                'job_id'  => $job->id
            ];
            rep()->userJob->m()->where('user_id', $user->id)->delete();
            rep()->userJob->m()->create($jobCreateData);
            rep()->userAttrAudit->m()->create([
                'user_id'      => $user->id,
                'key'          => 'job',
                'value'        => '',
                'check_status' => UserAttrAudit::STATUS_PASS,
                'done_at'      => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        $userDetailField      = ['height', 'weight', 'region'];
        $userDetailExtraField = ['emotion', 'child', 'education', 'income', 'figure', 'smoke', 'drink', 'hobby'];
        $charmUpdateData      = ['nickname', 'intro'];
        $updateData           = [];
        $detailExtraData      = [];
        $hobbyData            = [];
        $attrAuditData        = [];
        foreach ($userDetailExtraField as $item) {
            if (key_exists($item, $reqPost)) {
                if ($item == 'hobby') {
                    $hobbys = [];
                    $tags   = rep()->tag->m()->whereIn('uuid', $reqPost[$item])->get();
                    foreach ($tags as $tag) {
                        $hobbys[$tag->uuid] = $tag->id;
                    }
                    foreach ($reqPost[$item] as $value) {
                        $hobbyData[] = [
                            'uuid'       => pocket()->util->getSnowflakeId(),
                            'user_id'    => $user->id,
                            'tag_id'     => $hobbys[$value],
                            'created_at' => $now,
                            'updated_at' => $now
                        ];
                    }
                } else {
                    $detailExtraData[$item] = $reqPost[$item];
                }
                $attrAuditData[] = [
                    'user_id'      => $user->id,
                    'key'          => $item,
                    'value'        => '',
                    'check_status' => UserAttrAudit::STATUS_PASS,
                    'done_at'      => $now,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }
        $transDetailExtraData = pocket()->account->getDetailExtraUpdateArr($detailExtraData);
        foreach ($userDetailField as $item) {
            if (key_exists($item, $reqPost)) {
                $updateData[$item] = $reqPost[$item];
                $attrAuditData[]   = [
                    'user_id'      => $user->id,
                    'key'          => $item,
                    'value'        => '',
                    'check_status' => UserAttrAudit::STATUS_PASS,
                    'done_at'      => $now,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }
        DB::transaction(function () use ($user, $updateData, $transDetailExtraData, $hobbyData, $attrAuditData) {
            rep()->userDetail->m()->where('user_id', $user->id)->update($updateData);
            rep()->userDetailExtra->m()->where('user_id', $user->id)->update($transDetailExtraData);
            rep()->userAttrAudit->m()->insert($attrAuditData);
            if (count($hobbyData) > 0) {
                $hobbyTags = rep()->tag->m()->where('type', Tag::TYPE_HOBBY)->get()->pluck('id')->toArray();
                rep()->userTag->m()->where('user_id', $user->id)->whereIn('tag_id', $hobbyTags)->delete();
                rep()->userTag->m()->insert($hobbyData);
            }
        });
        if (!in_array(Role::KEY_CHARM_GIRL, explode(',', $user->role))) {
            $message = trans('messages.nickname_checking');
        }

        foreach ($charmUpdateData as $item) {
            switch ($item) {
                case 'nickname':
                    if (!key_exists('nickname', $reqPost) || $user->nickname == $reqPost['nickname']) {
                        continue 2;
                    }
                    $uu = rep()->user->getUserByNickName($reqPost['nickname']);
                    if (count($uu) > 0 && $user->nickname != $reqPost['nickname']) {
                        return ResultReturn::failed(trans('messages.nickname_repeat_error'));
                    }
                    $key        = trans('messages.nickname');
                    $businessId = config('netease.keys.dun.user_nickname');
                    break;
                case 'intro':
                    if (!key_exists('intro', $reqPost) || $userDetail->intro == $reqPost['intro']) {
                        continue 2;
                    }
                    $key        = trans('messages.sign');
                    $businessId = config('netease.keys.dun.user_intro');
                    break;
                default:
                    return ResultReturn::failed(trans('messages.not_found_key_tmpl'));
            }
            if (isset($reqPost[$item])) {
                $checkExist = rep()->userAttrAudit->m()
                    ->whereIn('check_status', [UserAttrAudit::STATUS_DELAY, UserAttrAudit::STATUS_ANTI_PASS])
                    ->where('key', $item)
                    ->where('user_id', $user->id)
                    ->first();
                if (!$checkExist) {
                    $charmMember = false;
                    $overTime    = false;
                    $timeCheck   = rep()->userAttrAudit->m()
                        ->where('check_status', UserAttrAudit::STATUS_PASS)
                        ->where('key', $item)
                        ->where('done_at', '!=', 0)
                        ->where('user_id', $user->id)
                        ->orderByDesc('id')
                        ->first();
                    $userMember  = rep()->member->getUserValidMember($user->id);
                    if ($userMember) {
                        $charmMember = true;
                    }
                    if ($charmMember) {
                        if ($timeCheck && (time() - $timeCheck->done_at < User::DETAIL_MEMBER_CHANGE_TIME)) {
                            $overTime = true;
                        }
                        $sendMessage = sprintf(trans('messages.vip_modify_limit_tmpl'), $key);
                    } else {
                        if ($timeCheck && (time() - $timeCheck->done_at < User::DETAIL_CHANGE_TIME)) {
                            $overTime = true;
                        }
                        $sendMessage = sprintf(trans('messages.five_day_modify_limit_tmpl'), $key);
                    }
                    if ($overTime) {
                        pocket()->common->sendNimMsgQueueMoreByPocketJob(
                            pocket()->netease,
                            'msgSendMsg',
                            [config('custom.little_helper_uuid'), $user->uuid, $sendMessage]
                        );
                    } else {
                        $result = pocket()->neteaseDun->checkText(get_md5_random_str(), $reqPost[$item], $businessId,
                            $user->uuid);
                        if ($result->getStatus() == false) {
                            return ResultReturn::failed(sprintf(trans('messages.check_error_tmpl'), $key));
                        }
                        $checkData   = $result->getData();
                        $checkStatus = $checkData['check_status'];
                        if ($checkStatus != 100) {
                            return ResultReturn::failed(sprintf(trans('messages.law_forbid_modify_submit_tmpl'), $key));
                        }
                        $updateData = [
                            'user_id'      => $user->id,
                            'key'          => $item,
                            'value'        => $reqPost[$item],
                            'check_status' => UserAttrAudit::STATUS_DELAY
                        ];
                        rep()->userAttrAudit->m()->create($updateData);
                        $sendMessage = sprintf(trans('messages.submitted_checking_tmpl'), $key);
                        pocket()->common->sendNimMsgQueueMoreByPocketJob(pocket()->netease, 'msgSendMsg',
                            [config('custom.little_helper_uuid'), $user->uuid, $sendMessage]);
                    }
                } else {
                    $sendMessage = sprintf(trans('messages.checking_not_modify_tmpl'), $key);
                    pocket()->common->sendNimMsgQueueMoreByPocketJob(pocket()->netease, 'msgSendMsg',
                        [config('custom.little_helper_uuid'), $user->uuid, $sendMessage]);
                }
            }
        }

        return ResultReturn::success([], $message);
    }

    /**
     * 给用户增加提现地址
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendUserWithdrawUrlToUser(User $user)
    {
        //        $user->setAttribute('withdraw_url', web_url('withdraw'));
        $user->setAttribute('withdraw_url', 'https://www.baidu.com/');

        return $user;
    }

    /**
     * 普通用户认证魅力女生后台操作
     *
     * @param $user
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function adminSimpleCharmAuth($user)
    {
        $userReview = rep()->userReview->m()
            ->where('user_id', $user->id)
            ->whereIn('check_status', [
                UserReview::CHECK_STATUS_DELAY,
                UserReview::CHECK_STATUS_BLACK_DELAY,
                UserReview::CHECK_STATUS_IGNORE,
                UserReview::CHECK_STATUS_BLACK_IGNORE,
                UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE,
                UserReview::CHECK_STATUS_FOLLOW_WECHAT
            ])
            ->orderByDesc('id')
            ->first();
        $wechat     = rep()->wechat->m()
            ->where('user_id', $user->id)
            ->where('check_status', Wechat::STATUS_DELAY)
            ->first();
        $Role       = rep()->role->m()
            ->where('key', Role::KEY_CHARM_GIRL)
            ->first();
        if (!$userReview) {
            return ResultReturn::failed('尚未提交审核资料');
        }
        $updateDetails = [
            'region' => $userReview->region,
            'height' => $userReview->height,
            'weight' => $userReview->weight,
            'intro'  => $userReview->intro
        ];
        $updateUser    = [
            'nickname'      => $userReview->nickname,
            'birthday'      => strtotime($userReview->birthday),
            'role'          => $user->role . ',charm_girl',
            'charm_girl_at' => time(),
        ];
        $updateJob     = [
            'uuid'    => pocket()->util->getSnowflakeId(),
            'user_id' => $user->id,
            'job_id'  => $userReview->job
        ];
        DB::transaction(function () use (
            $user,
            $updateDetails,
            $updateUser,
            $updateJob,
            $userReview,
            $wechat
        ) {
            $userReview->update(['check_status' => UserReview::CHECK_STATUS_PASS, 'done_at' => time()]);
            $wechat->update(['check_status' => Wechat::STATUS_PASS]);
            rep()->userJob->m()->where('user_id', $user->id)->delete();
            rep()->userJob->m()->create($updateJob);
            rep()->userDetail->m()->where('user_id', $user->id)->update($updateDetails);
            rep()->user->m()->where('id', $user->id)->update($updateUser);
            $userAvatar = rep()->resource->m()->where('related_id', $user->id)->where('related_type',
                UserResource::TYPE_AVATAR)->first();
            pocket()->netease->userUpdateUinfo($user->uuid, $updateUser['nickname'], file_url($userAvatar->resource));
            //            rep()->userRole->m()->create($userRoleData);
            pocket()->userRole->createUserRole($user, User::ROLE_CHARM_GIRL);
            $job = (new UpdateUserInfoToMongoJob($user->id))->onQueue('update_user_info_to_mongo');
            dispatch($job);
        });
        $message   = trans('messages.charm_girl_pass', [], $user->language);
        $data      = [
            'type' => NeteaseCustomCode::CHARM_GIRL_AUTH,
            'data' => ['status' => 'pass', 'message' => $message]
        ];
        $extention = ['pushcontent' => $message];
        pocket()->common->sendNimMsgQueueMoreByPocketJob(
            pocket()->netease,
            'msgSendCustomMsg',
            [config('custom.little_helper_uuid'), $user->uuid, $data, $extention]
        );
        pocket()->tengYu->sendXiaoquanUserContent($user, 'charm_pass');
        pocket()->push->pushToUser($user, trans('messages.charm_girl_has_passed', [], $user->language));
        pocket()->common->commonQueueMoreByPocketJob(pocket()->stat, 'statUserRegister',
            [$user->id, time(), 'charm_girl'], 10);
        pocket()->gio->report($user->uuid, GIOPocket::EVENT_CHARM_AUTH_PASS, []);
        //        pocket()->common->sendNimMsgQueueMoreByPocketJob(
        //            pocket()->netease,
        //            'msgSendMsg',
        //            [config('custom.little_helper_uuid'), $user->uuid, $message]
        //        );

        return ResultReturn::success([]);
    }

    /**
     * 上传用户经纬度
     *
     * @param  int  $userId
     * @param  int  $lng
     * @param  int  $lat
     *
     * @return User|Model
     */
    public function updateLocation(int $userId, $lng = 0, $lat = 0)
    {
        $user = rep()->user->getById($userId);
        if (!$user) {
            return $user;
        }

        $mongoUser = mongodb('user')->where('_id', $user->id)->first();
        if ($mongoUser && isset($mongoUser['location'][0]) && $mongoUser['location'][0] != 0) {
            $lng = $lng == 0 ? $mongoUser['location'][0] : $lng;
            $lat = $lat == 0 ? $mongoUser['location'][1] : $lat;
        }

        $uploadLocation = User::MONGO_LOC_IS_UPLOAD;
        if ($lng == 0 && $lat == 0) {
            $uploadLocation = User::MONGO_LOC_NOT_UPLOAD;
        }

        $cityName = pocket()->userDetail->getCityByLoc($lng, $lat);
        $city     = rep()->area->m()->select(['id', 'level', 'name', 'pid'])
            ->where('name', $cityName)
            ->where('level', 2)
            ->first();
        $cityId   = $provinceId = 0;
        if ($city) {
            $cityId     = $city->id;
            $provinceId = $city->pid;
        }

        mongodb('user')->where('_id', $user->id)->update([
            '_id'             => $user->id,
            'gender'          => $user->gender,
            'charm_girl'      => (int)pocket()->user->hasRole($user, User::ROLE_CHARM_GIRL),
            'upload_location' => $uploadLocation,
            'location'        => [(float)$lng, (float)$lat],
            'city_id'         => $cityId,
            'province_id'     => $provinceId,
        ], ['upsert' => true]);

        return $user;
    }

    /**
     * 获取人脸比对底图
     *
     * @param $userId
     *
     * @return \Illuminate\Http\JsonResponse|mixed|string
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function getBasePic($userId)
    {
        $faceRecord = rep()->facePic->m()->where('user_id', $userId)->orderByDesc('id')->first();
        if (!$faceRecord) {
            $compare = rep()->resource->m()
                ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
                ->where('related_id', $userId)
                ->first();
            if (!$compare) {
                return api_rr()->notFoundResult(trans('messages.get_base_img_fail'));
            }
            $comparePic = cdn_url($compare->resource);
        } else {
            $comparePic = cdn_url($faceRecord->base_map);
        }

        return $comparePic;
    }

    /**
     * 上传用户头像
     *
     * @param $avatar
     * @param $userRoles
     * @param $user
     * @param $force
     *
     * @return ResultReturn
     * @throws GuzzleException
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function uploadUserAvatar($avatar, $userRoles, $user, $force)
    {
        $avatarDetail = pocket()->account->getImagesDetail([$avatar]);
        if (count(array_intersect([Role::KEY_CHARM_GIRL, Role::KEY_AUTH_USER], $userRoles)) > 0) {
            $pornCheck = pocket()->neteaseDun->checkImages([$avatar], config('netease.keys.dun.user_avatar'),
                $user->uuid);
            if ($pornCheck->getStatus() == false) {
                return ResultReturn::failed(trans('messages.check_avatar_fail'));
            }
            $pornStatus = $pornCheck->getData()['check_status'];
            $message    = $pornCheck->getData()['content'];
            if ($pornStatus[$avatar] == 2) {
                $message = sprintf($message[$avatar], '头像');

                return ResultReturn::failed($message, ['status' => 'porn']);
            }
            $isRealFace = pocket()->aliYun->getDetectFaceResponse(cdn_url($avatar));
            $realData   = $isRealFace->getData();
            if (!$realData || count($realData['FaceInfos']['FaceAttributesDetectInfo']) == 0) {
                if (!$force) {
                    return ResultReturn::failed(trans('messages.upload_complete_avatar'));
                }
            }
            $comparePic = pocket()->account->getBasePic($user->id);
            $result     = pocket()->aliYun->getCompareResponse(
                $comparePic,
                cdn_url($avatar)
            );
            $data       = $result->getData();
            if ($result->getMessage() == 'No face detected from given images'
                || !$data
                || !key_exists('SimilarityScore', $data)
                || $data['SimilarityScore'] < User::ALIYUN_TEST_THRESHOLD) {
                if (!$force) {
                    if ($data && key_exists('SimilarityScore', $data)) {
                        return ResultReturn::failed(sprintf(trans('messages.avatar_to_img_similar_low'),
                            intval($data['SimilarityScore'])));
                    }

                    return ResultReturn::failed(trans('messages.img_check_fail_retry'));
                } else {
                    $userRole    = explode(',', $user->role);
                    $newUserRole = array_diff($userRole, ['auth_user']);
                    $user->update(['role' => implode(',', $newUserRole)]);
                }
            }
        } else {
            $pornCheck = pocket()->neteaseDun->checkImages([$avatar], config('netease.keys.dun.user_avatar'),
                $user->uuid);
            if ($pornCheck->getStatus() == false) {
                return ResultReturn::failed(trans('messages.check_avatar_fail'));
            }
            $pornStatus = $pornCheck->getData()['check_status'];
            $message    = $pornCheck->getData()['content'];
            if ($pornStatus[$avatar] == 2) {
                $message = sprintf($message[$avatar], '头像');

                return ResultReturn::failed($message, ['status' => 'porn']);
            }
        }
        if ($avatarDetail->getStatus() == false) {
            return ResultReturn::failed(trans('messages.get_img_info_failed_error'));
        }
        $resourceData = [
            'uuid'         => pocket()->util->getSnowflakeId(),
            'related_type' => Resource::RELATED_TYPE_USER_AVATAR,
            'related_id'   => $user->id,
            'type'         => Resource::TYPE_IMAGE,
            'resource'     => $avatar,
            'height'       => $avatarDetail->getData()[$avatar]['height'],
            'width'        => $avatarDetail->getData()[$avatar]['width'],
            'sort'         => 100
        ];
        try {
            DB::transaction(function () use ($user, $resourceData) {
                $oldRes = rep()->resource->m()
                    ->where('related_id', $user->id)
                    ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
                    ->orderBy('id')
                    ->first();
                $oldRes ? $oldRes->delete() : [];
                $resourceId       = rep()->resource->m()->create($resourceData)->id;
                $userResourceData = [
                    'uuid'        => pocket()->util->getSnowflakeId(),
                    'user_id'     => $user->id,
                    'type'        => UserResource::TYPE_AVATAR,
                    'resource_id' => $resourceId,
                ];
                rep()->userResource->m()->create($userResourceData);
                $job = (new UpdateUserInfoToMongoJob($user->id))->onQueue('update_user_info_to_mongo');
                dispatch($job);
            });
            pocket()->netease->userUpdateUinfo($user->uuid, '', file_url($avatar));
        } catch (\Exception $exception) {
            return ResultReturn::failed($exception->getMessage());
        }
        $final = [
            'resource' => $avatar,
            'preview'  => file_url($avatar)
        ];

        return ResultReturn::success($final);
    }

    /**
     * 上传用户相册
     *
     * @param $photos
     * @param $userRoles
     * @param $user
     * @param $now
     *
     * @return ResultReturn
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function uploadUserPhoto($photos, $userRoles, $user, $now)
    {
        if (in_array(config('custom.check_resource.url'), $photos)) {
            return ResultReturn::failed(trans('messages.img_not_checked'));
        }
        $exists = rep()->resource->m()
            ->where('related_id', $user->id)
            ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
            ->get();
        DB::beginTransaction();
        $wannaDelResourcesId = rep()->resource->m()
            ->where('related_id', $user->id)
            ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
            ->whereNotIn('resource', $photos)
            ->pluck('id')->toArray();
        rep()->resource->m()->whereIn('id', $wannaDelResourcesId)->delete();
        rep()->userPhoto->m()->whereIn('resource_id', $wannaDelResourcesId)->delete();
        $photos = array_diff($photos, $exists->pluck('resource')->toArray());
        if (count($photos) == 0) {
            DB::commit();

            return ResultReturn::success([]);
        }
        $finalData     = [];
        $resourceDatas = $userPhoto = [];
        $checkPic      = config('custom.check_resource.url');
        for ($i = 0; $i < count($photos); $i++) {
            $resourceDatas[] = [
                'uuid'         => pocket()->util->getSnowflakeId(),
                'related_type' => Resource::RELATED_TYPE_USER_PHOTO,
                'related_id'   => $user->id,
                'type'         => Resource::TYPE_IMAGE,
                'resource'     => $checkPic,
                'height'       => config('custom.check_resource.height'),
                'width'        => config('custom.check_resource.width'),
                'sort'         => 100,
                'created_at'   => $now,
                'updated_at'   => $now
            ];
        }
        rep()->resource->m()->insert($resourceDatas);
        $realResources = rep()->resource->m()
            ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
            ->where('related_id', $user->id)
            ->whereNotIn('resource', $exists->pluck('resource')->toArray())
            ->lockForUpdate()
            ->get();
        if (count($realResources->pluck('id')->toArray()) != count($photos)) {
            return ResultReturn::failed(trans('messages.upload_photos_fail'));
        }
        $resourceArr = array_combine($realResources->pluck('id')->toArray(), $photos);
        $checkDatas  = $userPhoto = [];
        foreach ($realResources as $item) {
            $checkDatas[] = [
                'related_type' => ResourceCheck::RELATED_TYPE_USER_PHOTO,
                'related_id'   => $user->id,
                'resource_id'  => $item->id,
                'resource'     => $resourceArr[$item->id],
                'status'       => ResourceCheck::STATUS_DELAY,
                'created_at'   => $now,
                'updated_at'   => $now
            ];
        }
        $checkDatas && rep()->resourceCheck->m()->insert($checkDatas);
        pocket()->common->commonQueueMoreByPocketJob(
            pocket()->account,
            'checkAuthPhoto',
            [$user, user_agent()->clientVersion]
        );
        $resources = rep()->resource->m()
            ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
            ->where('related_id', $user->id)
            ->get();
        foreach ($resources as $resource) {
            if (!in_array($resource->id, $exists->pluck('id')->toArray())) {
                $finalData[] = [
                    'resource' => $resource->resource,
                    'preview'  => file_url($resource->resource)
                ];
                $userPhoto[] = [
                    'user_id'      => $user->id,
                    'resource_id'  => $resource->id,
                    'related_type' => UserPhoto::RELATED_TYPE_FREE,
                    'amount'       => 0,
                    'status'       => UserPhoto::STATUS_OPEN,
                    'created_at'   => $now,
                    'updated_at'   => $now
                ];
            }
        }
        $userPhoto && rep()->userPhoto->m()->insert($userPhoto);
        DB::commit();

        return ResultReturn::success($finalData);
    }

    /**
     * 异步检测用户相册
     *
     * @param $user
     * @param $version
     *
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function checkAuthPhoto($user, $version)
    {
        $successCount      = 0;
        $failCount         = 0;
        $videoSuccessCount = 0;
        $videoFailCount    = 0;
        $pornCount         = 0;
        $checkPhotos       = rep()->resourceCheck->m()
            ->whereIn('related_type', [ResourceCheck::RELATED_TYPE_USER_PHOTO, ResourceCheck::RELATED_TYPE_USER_VIDEO])
            ->where('status', ResourceCheck::STATUS_DELAY)
            ->where('related_id', $user->id)
            ->get();
        $pornChecks        = pocket()->neteaseDun->checkImages($checkPhotos->pluck('resource')->toArray(),
            config('netease.keys.dun.user_photo'), $user->uuid);
        $pornStatusData    = $pornChecks->getData();
        if (key_exists('check_status', $pornStatusData)) {
            $pornStatus = $pornStatusData['check_status'];
        } else {
            $pornStatus = [];
        }
        if (key_exists('content', $pornStatusData)) {
            $content = $pornStatusData['content'];
        } else {
            $content = [];
        }
        foreach ($checkPhotos as $item) {
            if ($item->related_type == ResourceCheck::RELATED_TYPE_USER_PHOTO) {
                //鉴黄
                if (!key_exists($item->resource, $pornStatus) || $pornStatus[$item->resource] == 2) {
                    $pornCount++;
                    $failCount++;
                    $this->setResourceCheckResult(false, $item);
                    continue;
                }
                //非魅力女生只过鉴黄不过人脸
                if (!in_array(Role::KEY_CHARM_GIRL, explode(',', $user->role))) {
                    $successCount++;
                    $this->setResourceCheckResult(true, $item);
                    continue;
                }
                //用户是否有同步检测过人脸
                $isChecked = mongodb('upload_file_record')
                    ->where('type', 'user_photo')
                    ->where('related_id', (int)$user->uuid)
                    ->where('path', $item->resource)
                    ->where('checked', true)
                    ->first();
                //检查过的在mongo
                if ($isChecked) {
                    $successCount++;
                    $this->setResourceCheckResult(true, $item);
                    continue;
                }
                //人脸属性检测
                $isRealFace = pocket()->aliYun->getDetectFaceResponse(cdn_url($item->resource));
                $realData   = $isRealFace->getData();
                if (!$realData || count($realData['FaceInfos']['FaceAttributesDetectInfo']) == 0) {
                    $failCount++;
                    $this->setResourceCheckResult(false, $item);
                    continue;
                }
                //人脸比对
                $comparePic = pocket()->account->getBasePic($user->id);
                $result     = pocket()->aliYun->getCompareResponse($comparePic, cdn_url($item->resource));
                if ($result->getStatus() == false || $result->getData()['SimilarityScore'] < User::ALIYUN_TEST_THRESHOLD) {
                    $failCount++;
                    $this->setResourceCheckResult(false, $item);
                } else {
                    $successCount++;
                    $this->setResourceCheckResult(true, $item);
                }
            } elseif ($item->related_type == ResourceCheck::RELATED_TYPE_USER_VIDEO) {
                $video = rep()->resource->getById($item->resource_id);
                if (!$video) {
                    DB::transaction(function () use ($item) {
                        rep()->userPhoto->m()->where('resource_id', $item->resource_id)->delete();
                        rep()->resourceCheck->m()->where('resource_id', $item->resource_id)->delete();
                    });
                    $videoFailCount++;
                    continue;
                }
                $videoSuccessCount++;
                $checkResource = rep()->resourceCheck->m()->where('resource_id', $video->id)->first();
                pocket()->fengkong->sendPornCheckRequest($video->uuid, cdn_url($checkResource->resource));
                pocket()->common->sendNimMsgQueueMoreByPocketJob(pocket()->netease, 'msgSendMsg',
                    [
                        config('custom.little_helper_uuid'),
                        $user->uuid,
                        //                        trans('messages.in_the_video_audit', [], $user->language)
                        '视频审核中，审核通过后用户即可看到~'
                    ]
                );
            }
        }
        if ($successCount != 0 || $failCount != 0) {
            if (version_compare($version, '1.6.0', '>=')) {
                if ($successCount) {
                    $message = trans('messages.photos_upload_success', [], $user->language);
                } elseif ($failCount) {
                    if ($pornCount) {
                        $message = trans('messages.avatar_law_forbid_upload_green', [], $user->language);
                    } else {
                        $message = trans('messages.img_check_fail_retry_upload', [], $user->language);
                    }
                }
            } else {
                $message = sprintf(trans('messages.upload_many_imgs_tmpl', [], $user->language),
                    $successCount, $failCount);
            }
            pocket()->common->sendNimMsgQueueMoreByPocketJob(
                pocket()->netease,
                'msgSendMsg',
                [config('custom.little_helper_uuid'), $user->uuid, $message]
            );
        }
        $job = (new UpdateUserInfoToMongoJob($user->id))->onQueue('update_user_info_to_mongo');
        dispatch($job);
    }

    /**
     * 设置图片检测结果
     *
     * @param $status
     * @param $item
     */
    public function setResourceCheckResult($status, $item)
    {
        if ($status) {
            DB::transaction(function () use ($item) {
                rep()->resource->m()->where('id', $item->resource_id)->update(['resource' => $item->resource]);
                rep()->userPhoto->m()->where('resource_id',
                    $item->resource_id)->update(['status' => UserPhoto::STATUS_OPEN]);
                rep()->resourceCheck->m()
                    ->where('id', $item->id)
                    ->update(['status' => ResourceCheck::STATUS_PASS, 'deleted_at' => time()]);
                $userPhoto = rep()->userPhoto->m()->where('resource_id', $item->resource_id)->first();
                if ($userPhoto) {
                    $userPhoto->update(['status' => UserPhoto::STATUS_OPEN]);
                }
            });
        } else {
            DB::transaction(function () use ($item) {
                rep()->resource->m()->where('id', $item->resource_id)->delete();
                rep()->userPhoto->m()->where('resource_id', $item->resource_id)->delete();
                rep()->resourceCheck->m()
                    ->where('id', $item->id)
                    ->update(['status' => ResourceCheck::STATUS_COMPARE_FAIL, 'deleted_at' => time()]);
                $userPhoto = rep()->userPhoto->m()->where('resource_id', $item->resource_id)->first();
                if ($userPhoto) {
                    $userPhoto->delete();
                }
            });
        }
    }

    /**
     * 检测用户修改的信息（机审）
     *
     * @param $user
     * @param $type
     *
     * @return ResultReturn
     * @throws \Exception
     */
    public function checkUserDetail($user, $type)
    {
        $status   = false;
        $userAttr = rep()->userAttrAudit->m()
            ->where('user_id', $user->id)
            ->where('key', $type)
            ->where('check_status', UserAttrAudit::STATUS_DELAY)
            ->first();
        if (!$userAttr) {
            return ResultReturn::failed(trans('messages.not_need_checked'));
        }
        if ($userAttr->type == 'nickname') {
            $businessId = config('netease.keys.dun.user_nickname');
        } elseif ($userAttr->type == 'intro') {
            $businessId = config('netease.keys.dun.user_intro');
        }
        $result = pocket()->neteaseDun->checkText(get_md5_random_str(), $userAttr->value, $businessId, $user->uuid);
        if ($result->getStatus() == true) {
            $checkData   = $result->getData();
            $checkStatus = $checkData['check_status'];
            if ($checkStatus == 100) {
                $status = true;
            }
        }
        if ($status) {
            switch ($type) {
                case 'nickname':
                    DB::transaction(function () use ($userAttr, $user) {
                        rep()->user->m()->where('id', $user->id)->update(['nickname' => $userAttr->value]);
                        $userAttr->update(['check_status' => UserAttrAudit::STATUS_PASS, 'done_at' => time()]);
                    });
                    $key = trans('messages.nickname', [], $user->language);
                    break;
                case 'intro':
                    DB::transaction(function () use ($userAttr, $user) {
                        rep()->userDetail->m()->where('user_id', $user->id)->update(['intro' => $userAttr->value]);
                        $userAttr->update(['check_status' => UserAttrAudit::STATUS_PASS, 'done_at' => time()]);
                    });
                    $key = trans('messages.sign', [], $user->language);
                    break;
            }
            $message = $key . '修改成功~';
        } else {
            switch ($type) {
                case 'nickname':
                    $key = trans('messages.nickname', [], $user->language);
                    break;
                case 'intro':
                    $key = trans('messages.sign', [], $user->language);
                    break;
            }
            $message = sprintf(trans('messages.modify_deleted_retry_upload_tmpl', [], $user->language), $key);
            $userAttr->update(['check_status' => UserAttrAudit::STATUS_FAIL]);
            $userAttr->delete();
        }
        pocket()->common->sendNimMsgQueueMoreByPocketJob(
            pocket()->netease,
            'msgSendMsg',
            [config('custom.little_helper_uuid'), $user->uuid, $message]
        );
    }

    /**
     * v2版男生上传相册
     *
     * @param $photos
     * @param $existPhotos
     * @param $user
     * @param $now
     *
     * @return ResultReturn
     * @throws \Exception
     */
    public function uploadPhotosManV2($photos, $existPhotos, $user, $now)
    {
        $manPhotos        = [];
        $needRetainPhotos = [];
        $payTypeList      = [];
        foreach ($photos as $item) {
            if ($item['type'] == 'video') {
                if ($user->gender == User::GENDER_MAN) {
                    return ResultReturn::failed(trans('messages.man_not_upload_video'));
                } else {
                    return ResultReturn::failed('女生请先完成认证即可上传视频');
                }
            }
            if (!in_array($item['url'], $existPhotos)) {
                $manPhotos[] = $item['url'];
            } else {
                $needRetainPhotos[] = $item['url'];
            }
            $payTypeList[$item['url']] = $item['pay_type'];
        }
        DB::beginTransaction();
        rep()->resource->m()
            ->where('related_id', $user->id)
            ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
            ->whereNotIn('resource', $needRetainPhotos)
            ->delete();
        if (count($manPhotos) != 0) {
            $photoDetails = pocket()->account->getImagesDetail($manPhotos);
            if ($photoDetails->getStatus() == false) {
                return ResultReturn::failed(trans('messages.get_img_detail_fail'));
            }
            foreach ($photoDetails->getData() as $key => $value) {
                $resourceDatas[] = [
                    'uuid'         => pocket()->util->getSnowflakeId(),
                    'related_type' => Resource::RELATED_TYPE_USER_PHOTO,
                    'related_id'   => $user->id,
                    'type'         => Resource::TYPE_IMAGE,
                    'resource'     => $key,
                    'height'       => $value['height'],
                    'width'        => $value['width'],
                    'sort'         => 100,
                    'created_at'   => $now,
                    'updated_at'   => $now
                ];
            }
            rep()->resource->m()->insert($resourceDatas);
        }
        $userPhotoData = [];
        $resources     = rep()->resource->m()
            ->where('related_id', $user->id)
            ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
            ->whereNotIn('resource', $needRetainPhotos)
            ->get();
        foreach ($resources as $resource) {
            if (!in_array($resource->resource, $needRetainPhotos)) {
                $userPhotoData[] = [
                    'user_id'      => $user->id,
                    'resource_id'  => $resource->id,
                    'related_type' => UserPhoto::EXTENSION_MAPPING[$payTypeList[$resource->resource]],
                    'amount'       => UserPhoto::AMOUNT_MAPPING[UserPhoto::EXTENSION_MAPPING[$payTypeList[$resource->resource]]],
                    'status'       => UserPhoto::STATUS_CLOSE,
                    'created_at'   => $now,
                    'updated_at'   => $now
                ];
            }
        }
        rep()->userPhoto->m()->insert($userPhotoData);
        $realResources = rep()->resource->m()
            ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
            ->where('related_id', $user->id)
            ->whereNotIn('resource', $existPhotos)
            ->lockForUpdate()
            ->get();
        if (count($realResources->pluck('id')->toArray()) != count($manPhotos)) {
            return ResultReturn::failed(trans('messages.upload_photos_fail'));
        }
        $resourceArr = array_combine($realResources->pluck('id')->toArray(), $manPhotos);
        $checkDatas  = $userPhoto = [];
        foreach ($realResources as $item) {
            $checkDatas[] = [
                'related_type' => ResourceCheck::RELATED_TYPE_USER_PHOTO,
                'related_id'   => $user->id,
                'resource_id'  => $item->id,
                'resource'     => $resourceArr[$item->id],
                'status'       => ResourceCheck::STATUS_DELAY,
                'created_at'   => $now,
                'updated_at'   => $now
            ];
        }
        $checkDatas && rep()->resourceCheck->m()->insert($checkDatas);
        DB::commit();
        pocket()->common->commonQueueMoreByPocketJob(
            pocket()->account,
            'checkAuthPhoto',
            [$user, user_agent()->clientVersion]
        );

        return ResultReturn::success([]);
    }

    /**
     * 获取用户是否能发送动态
     *
     * @param $user
     * @param $isCharmGirl
     * @param $isMember
     *
     * @return bool
     */
    public function getMomentWant($user, $isCharmGirl, $isMember)
    {
        $todayStart = strtotime(date('Y-m-d', time()));
        $todayEnd   = $todayStart + 86399;

        $moment = rep()->moment->m()->where('user_id', $user->id)
            ->where('check_status', '!=', Moment::CHECK_STATUS_FAIL)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        if ($user->gender == User::GENDER_MAN) {
            return $isMember && !$moment;
        } elseif ($isCharmGirl) {
            return !$moment || ($isMember && $moment < 3);
        }

        return false;
    }

    /**
     * 获取用户是否能私聊
     *
     * @param $user
     * @param $uuid
     *
     * @return array
     */
    public function getChatWant($user, $uuid)
    {
        $redisKey   = sprintf(config('redis_keys.is_chat.key'), $user->id);
        $startTime  = strtotime(date('Y-m-d'));
        $endTime    = $startTime + 86399;
        $chatArr    = redis()->client()->zRangeByScore($redisKey, $startTime, $endTime, ['withscores' => true]);
        $userMember = rep()->member->m()
            ->where('user_id', $user->id)
            ->where(DB::raw('start_at + duration'), '>', time())
            ->first();

        $result['alert_status'] = false;
        if (!key_exists($uuid, $chatArr) && $uuid != config('custom.little_helper_uuid')) {
            if ($userMember) {
                if (version_compare(user_agent()->clientVersion, '1.9.0', '>=')) {
                    $result['can_chat']     = (100 - count($chatArr) >= 0);
                    $result['count']        = (100 - count($chatArr)) < 0 ? 0 : (100 - count($chatArr));
                    $result['alert_status'] = false;
                } else {
                    $result['can_chat']     = true;
                    $result['count']        = 0;
                    $result['alert_status'] = false;
                }
            } else {
                $result['can_chat'] = (20 - count($chatArr) >= 0);
                $result['count']    = (20 - count($chatArr)) < 0 ? 0 : (20 - count($chatArr));
                if (20 - count($chatArr) <= 3) {
                    $result['alert_status'] = true;
                }
            }
        } else {
            $result['can_chat']     = true;
            $result['count']        = 0;
            $result['alert_status'] = false;
        }

        return $result;
    }

    /**
     * 获取用户是否能修改信息
     *
     * @param $user
     *
     * @return bool
     */
    public function getUpdateWant($user)
    {
        if ($user->gender == User::GENDER_MAN) {
            return true;
        }
        $userReview = rep()->userReview->m()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();
        if ($userReview && $userReview->check_status == UserReview::CHECK_STATUS_DELAY) {
            return false;
        }
        $timeCheck = rep()->userAttrAudit->m()
            //            ->where('done_at', '!=', 0)
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();
        if (!$timeCheck) {
            return true;
        }
        if ($timeCheck->check_status != UserAttrAudit::STATUS_DELAY) {
            if ($timeCheck->check_status == UserAttrAudit::STATUS_FAIL) {
                return true;
            }
            $userMember = rep()->member->getUserValidMember($user->id);
            if ($userMember) {
                if ($timeCheck && ($timeCheck->done_at != 0) && (time() - $timeCheck->done_at >= User::DETAIL_MEMBER_CHANGE_TIME)) {
                    return true;
                }
            } else {
                if ($timeCheck && ($timeCheck->done_at != 0) && (time() - $timeCheck->done_at) >= User::DETAIL_CHANGE_TIME) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 获取用户是否能修改信息
     *
     * @param $user
     *
     * @return array
     */
    public function getUpdateWantV2($user) : array
    {
        if ($user->gender == User::GENDER_MAN) {
            return ['status' => true, 'content' => ''];
        }
        $userReview = rep()->userReview->m()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();
        if ($userReview && $userReview->check_status == UserReview::CHECK_STATUS_DELAY) {
            return ['status' => false, 'content' => '审核中无法修改资料'];
        }
        $timeCheck = rep()->userAttrAudit->m()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();
        if (!$timeCheck) {
            return ['status' => true, 'content' => ''];
        }
        if ($timeCheck->check_status != UserAttrAudit::STATUS_DELAY) {
            if ($timeCheck->check_status == UserAttrAudit::STATUS_FAIL) {
                return ['status' => true, 'content' => ''];
            }
            $userMember     = rep()->member->getUserValidMember($user->id);
            $timeDifference = time() - $timeCheck->done_at;
            if ($userMember) {
                if (($timeCheck->done_at != 0) && ($timeDifference >= User::DETAIL_MEMBER_CHANGE_TIME)) {
                    return ['status' => true, 'content' => ''];
                } else {
                    if (intval((User::DETAIL_MEMBER_CHANGE_TIME - $timeDifference) / 3600) > 1) {
                        $frontText = intval((User::DETAIL_MEMBER_CHANGE_TIME - $timeDifference) / 3600) . '小时';
                    } else {
                        if (intval((User::DETAIL_MEMBER_CHANGE_TIME - $timeDifference) / 60) > 1) {
                            $frontText = intval((User::DETAIL_MEMBER_CHANGE_TIME - $timeDifference) / 60) . '分钟';
                        } else {
                            $frontText = '1分钟';
                        }
                    }

                }
            } else {
                if (($timeCheck->done_at != 0) && $timeDifference >= User::DETAIL_CHANGE_TIME) {
                    return ['status' => true, 'content' => ''];
                } else {
                    if (intval((User::DETAIL_CHANGE_TIME - $timeDifference) / 86400) > 0) {
                        $frontText = intval((User::DETAIL_CHANGE_TIME - $timeDifference) / 86400) . '天' . intval(((User::DETAIL_CHANGE_TIME - $timeDifference) % 86400) / 3600) . '小时';
                    } else {
                        if (intval((User::DETAIL_CHANGE_TIME - $timeDifference) / 3600) > 1) {
                            $frontText = intval((User::DETAIL_CHANGE_TIME - $timeDifference) / 3600) . '小时';
                        } else {
                            $frontText = '1小时';
                        }
                    }
                }
            }
        } else {
            return ['status' => false, 'content' => '修改资料申请审核中~'];
        }

        return ['status' => false, 'content' => $frontText . '后可再次修改资料~'];
    }

    /**
     * 上传人脸图片
     *
     * @param $filepath
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function uploadFaceAuth($filepath)
    {
        $client   = new \GuzzleHttp\Client();
        $response = $client->post(
            file_url('file/single?type=face_auth'),
            [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($filepath, 'r'),
                    ]
                ]
            ]
        );
        if ($response->getStatusCode() != 200) {
            return ResultReturn::failed(trans('messages.http_code_not_200_tmpl'));
        }

        return ResultReturn::success(json_decode($response->getBody()->getContents()));
    }

    /**
     * 检测拉黑人脸改变审核状态
     *
     * @param $reviewId
     *
     * @return ResultReturn
     */
    public function checkFaceGreen($reviewId)
    {
        $userReview = rep()->userReview->getById($reviewId);
        if (!$userReview) {
            return ResultReturn::failed('未找到审核详情');
        }
        $user   = rep()->user->getById($userReview->user_id);
        $result = pocket()->aliGreen->sfaceImageCompare($user->uuid, SfaceRecord::GROUP_FACE_BLACK);
        if ($result->getStatus() == false) {
            return ResultReturn::failed(trans('messages.face_checked_error'));
        }
        $data = $result->getData();
        // 校验阿里校验人脸检索返回的结果
        if (key_exists('results', $data['data'][0])) {
            $topPerson = $data['data'][0]['results'][0]['topPersonData'][0];
            if (key_exists('persons', $topPerson)) {
                $rate = $topPerson['persons'][0]['rate'];
                if (($rate * 100) > 90) {
                    if ($userReview->check_status == UserReview::CHECK_STATUS_DELAY) {
                        $userReview->update(['check_status' => UserReview::CHECK_STATUS_BLACK_DELAY]);
                    }
                    if ($userReview->check_status == UserReview::CHECK_STATUS_FOLLOW_WECHAT) {
                        $userReview->update(['check_status' => UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE]);
                    }
                }
            }
        }

        return ResultReturn::success([]);
    }

    /**
     * 判断一个用户是否在审核中
     *
     * @param $user
     *
     * @return ResultReturn
     */
    public function checkUserReviewing($user)
    {
        if ($user->gender == User::GENDER_WOMEN && !in_array(Role::KEY_CHARM_GIRL, explode(',', $user->role))) {
            $userReview = rep()->userReview->m()->where('user_id', $user->id)->orderByDesc('id')->first();
            if ($userReview && in_array($userReview->check_status, [
                    UserReview::CHECK_STATUS_DELAY,
                    UserReview::CHECK_STATUS_BLACK_DELAY,
                    UserReview::CHECK_STATUS_BLACK_IGNORE,
                    UserReview::CHECK_STATUS_IGNORE,
                    UserReview::CHECK_STATUS_FOLLOW_WECHAT,
                    UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE,
                ])) {
                return ResultReturn::failed(trans('messages.checking'));
            }
        }

        return ResultReturn::success([]);
    }

    /**
     * 转换更多信息的更新数组
     *
     * @param $array
     *
     * @return array
     */
    public function getDetailExtraUpdateArr($array)
    {
        $tags              = rep()->tag->m()
            ->whereIn('uuid', array_values($array))
            ->get();
        $tagMapping        = [];
        $updateDetailExtra = [];
        foreach ($tags as $tag) {
            $tagMapping[$tag->uuid] = $tag->id;
        }
        foreach ($array as $key => $value) {
            $updateDetailExtra[$key] = $tagMapping[$value];
        }

        return $updateDetailExtra;
    }

    /**
     * 获取显示的评价分数
     *
     * @param $star
     *
     * @return float|int
     */
    public function getShowStar($star)
    {
        $float = $star - intval($star);
        if ($float == 0) {
            return intval($star);
        } elseif ($float > 0 && $float <= 0.25) {
            return intval($star);
        } elseif ($float > 0.25 && $float <= 0.5) {
            return intval($star) + 0.5;
        } elseif ($float > 0.5 && $float <= 0.75) {
            return intval($star) + 0.5;
        } elseif ($float > 0.75) {
            return intval($star) + 1;
        }

        return ResultReturn::success([]);

    }

    /**
     * 处理后台隐身的用户
     *
     * @param $userId
     *
     * @return ResultReturn
     */
    public function showUser($userId)
    {
        $switch     = rep()->switchModel->m()
            ->where('key', SwitchModel::KEY_ADMIN_HIDE_USER)
            ->first();
        $userSwitch = rep()->userSwitch->m()
            ->where('user_id', $userId)
            ->where('switch_id', $switch->id)
            ->first();
        if ($userSwitch) {
            $show = User::SHOW;
            try {
                DB::transaction(function () use ($userId, $switch, $show) {
                    rep()->userSwitch->m()
                        ->where('user_id', $userId)
                        ->where('switch_id', $switch->id)
                        ->update(['status' => $show]);
                    rep()->user->m()->where('id', $userId)->update([
                        'hide' => $show
                    ]);
                });
                $redisKey = config('redis_keys.hide_users.key');
                redis()->client()->sRem($redisKey, $userId);
            } catch (\Exception $e) {

                return ResultReturn::failed($e->getMessage());
            }

            pocket()->esUser->updateUserFieldToEs($userId, ['hide' => $show]);
        }

        return ResultReturn::success([]);
    }

    /**
     * 给用户设置真人认证角色并上传留底图
     *
     * @param $user
     * @param $facePic
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function setUserAuth($user, $facePic)
    {
        $userRole     = rep()->role->m()->where('key', Role::KEY_AUTH_USER)->first();
        $userRoleData = [
            'user_id' => $user->id,
            'role_id' => $userRole->id
        ];
        $user->update(['role' => $user->role . ',' . Role::KEY_AUTH_USER]);
        $userRoleExist = rep()->userRole->m()
            ->where('user_id', $user->id)
            ->where('role_id', $userRole->id)
            ->first();
        if (!$userRoleExist) {
            rep()->userRole->m()->create($userRoleData);
        }
        $faceAuthUpload = pocket()->account->uploadFaceAuth($facePic);
        if ($faceAuthUpload->getStatus() == false) {
            return ResultReturn::failed('上传留底图失败');
        }
        $filePath = $faceAuthUpload->getData()->data->resource;
        $picData  = [
            'user_id'  => $user->id,
            'base_map' => $filePath,
            'status'   => FacePic::STATUS_PASS
        ];
        rep()->facePic->m()->create($picData);

        return ResultReturn::success([]);
    }

    /**
     * 隐身
     *
     * @param $userId
     *
     * @return ResultReturn
     */
    public function hideUser($userId)
    {
        $hide     = User::HIDE;
        $user     = rep()->user->getById($userId);
        $uuid     = $user->uuid;
        $userLock = rep()->switchModel->m()
            ->where('key', SwitchModel::KEY_ADMIN_HIDE_USER)
            ->first();
        if (!$user || !$userLock) {
            return ResultReturn::failed("系统错误~");
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
            $redisKey = config('redis_keys.hide_users.key');
            redis()->client()->sAdd($redisKey, $user->id);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }

        pocket()->esUser->updateUserFieldToEs($user->id, ['hide' => $hide]);

        return ResultReturn::success([]);
    }

    /**
     * 上报注册成功信息
     *
     * @param $user
     */
    public function reportUserRegisterFinishData($userId)
    {
        $user         = rep()->user->getById($userId);
        $data         = [
            'sex_var'            => $user->gender == User::GENDER_WOMEN ? '女' : '男',
            'inviteUserID_var'   => '无',
            'InvitationCode_var' => '无',
            'numberType_var'     => '本机号码'
        ];
        $inviteRecord = rep()->inviteRecord->m()->where('target_user_id', $user->id)->where('status',
            InviteRecord::STATUS_SUCCEED)->first();
        if ($inviteRecord) {
            $inviteUser                 = rep()->user->m()->where('id', $inviteRecord->user_id)->first();
            $inviteUserDetail           = rep()->userDetail->m()->where('user_id', $inviteRecord->user_id)->first();
            $data['inviteUserID_var']   = (string)$inviteUser->uuid;
            $data['InvitationCode_var'] = (string)$inviteUserDetail->invite_code;
        }
        $sms = rep()->sms->m()->where('mobile', $user->mobile)->orderByDesc('id')->first();
        if ($sms) {
            if ($sms->type == Sms::TYPE_MOBILE_QUICKLY) {
                $data['numberType_var'] = '其他号码';
            }
        }
        pocket()->gio->report($user->uuid, GIOPocket::EVENT_REGISTER_SUCCESS, $data);
    }

    /**
     * 魅力女生认证前置操作
     *
     * @param $user
     *
     * @return array|object
     */
    public function getCharmAuthWant($user)
    {
        $userReviewSuccess = rep()->userReview->m()
            ->where('check_status', UserReview::CHECK_STATUS_PASS)
            ->where('user_id', $user->id)
            ->first();
        $userFailed        = rep()->userReview->m()
            ->where('check_status', UserReview::CHECK_STATUS_FAIL)
            ->where('user_id', $user->id)
            ->when($userReviewSuccess, function ($query) use ($userReviewSuccess) {
                $query->where('id', '>', $userReviewSuccess->id);
            })
            ->get();
        if (count($userFailed) == 0) {
            return ['status' => true, 'msg' => ''];
        }
        switch (count($userFailed)) {
            case 1:
            case 2:
            case 3:
                $time = 86400;
                break;
            case 4:
            case 5:
                $time = 86400 * 2;
                break;
            default:
                $time = 86400 * 3;
                break;
        }
        $remainTime = time() - $userFailed->last()->created_at->timestamp;
        if ($remainTime < $time) {
            return [
                'status' => false,
                'msg'    => '上次审核被拒绝，请于' . intval(($time - $remainTime) / 3600) . '时' . intval((($time - $remainTime) % 3600) / 60) . '分后再次提交认证'
            ];
        }

        return ['status' => true, 'msg' => ''];
    }

    /**
     * 给男生发送假的打招呼消息
     *
     * @param $userId
     * @param $remainDay
     *
     * @return ResultReturn
     */
    public function addUserFakeGreet($userId, $remainDay) : ResultReturn
    {
        // 注册、第二天活跃、第三天活跃发送的时间梯度不同
        switch ($remainDay) {
            case 1:
                $timeData = [0, 5, 10, 20, 25, 120];
                break;
            case 2 :
                $timeData = [0, 10, 13, 30, 70, 80];
                break;
            case 3:
                $timeData = [0, 5, 15, 30, 45, 50, 120, 150];
                break;
            default:
                $timeData = [];
                break;
        }
        $redisKeyFake = config('redis_keys.not_need_fake_users.key');
        $notNeedUsers = redis()->client()->sIsMember($redisKeyFake, $userId);
        $user         = rep()->user->getById($userId, ['gender']);
        // 铂金圈V1.2.2
        // 只有注册、活跃第二天、活跃第三天需要假的打招呼消息
        // 用户ID 0、1、2、3、4 结尾的账号发假的打招呼消息
        // 用户有任何充值操作就不再发假的打招呼
        if (count($timeData) == 0 || $userId % 10 < 5 || $notNeedUsers || $user->gender == User::GENDER_WOMEN) {
            return ResultReturn::failed('已经不需要发送了');
        }

        $userUUid = rep()->user->getById($userId, ['uuid'])->uuid;
        // 记录发送过的用户
        $redisKeyUsed = sprintf(config('redis_keys.has_used_users.key'), $userId);
        foreach ($timeData as $item) {
            $girlIdData = pocket()->esUser->getFakeGreetUser($userId);
            if ($girlIdData->getStatus() == false) {
                return ResultReturn::failed($girlIdData->getMessage());
            }
            $girlId = $girlIdData->getData()['girl_id'];
            $girl   = rep()->user->getById($girlId, ['uuid']);
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->netease,
                'msgSendBatchMsg',
                [$girl->uuid, [$userUUid], config('custom.greet.fake_greet')[array_rand(config('custom.greet.fake_greet'))]],
                $item
            );
            redis()->client()->sAdd($redisKeyUsed, $girlId);
        }

        return ResultReturn::success([]);
    }

    /**
     * 发送假打招呼
     *
     * @param $userId
     *
     * @return ResultReturn
     */
    public function postSetUserFakeGreet($userId) : ResultReturn
    {
        $remainCounts = rep()->statRemainLoginLog->m()->where('user_id', $userId)->count();
        if ($remainCounts > 1) {
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->account,
                'addUserFakeGreet',
                [$userId, $remainCounts]
            );
        }

        return ResultReturn::success([]);
    }

    /**
     * 设置用户为不需要发假打招呼的人
     *
     * @param $userId
     *
     * @return ResultReturn
     */
    public function setNotNeedFakeUser($userId) : ResultReturn
    {
        $redisKeyFake = config('redis_keys.not_need_fake_users.key');
        redis()->client()->sAdd($redisKeyFake, $userId);

        return ResultReturn::success([]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Constant\ApiBusinessCode;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\SwitchModel;
use App\Models\Trade;
use App\Models\TradeIncome;
use App\Models\TradeModel;
use App\Models\TradeWithdraw;
use App\Models\User;
use App\Models\Config;
use App\Models\Blacklist;
use App\Models\UserPhoto;
use App\Models\UserRelation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Jobs\UpdateChannelDataJob;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\User\UserChannelRequest;
use App\Http\Requests\Search\SearchUsersRequest;
use App\Models\TradeBuy;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\Redis;
use App\Models\Report;
use App\Models\UserVisit;
use App\Constant\NeteaseCustomCode;
use App\Models\Resource;
use App\Models\UserSwitch;

/**
 * Class UserController
 * @package App\Http\Controllers
 */
class UserController extends BaseController
{
    /**
     * 获取单个用户
     *
     * @param  Request  $request
     *
     * @param  string   $uuid  用户uuid
     *
     * @return JsonResponse
     */
    public function singleUser(Request $request, string $uuid)
    {
        if (!is_numeric($uuid)) {
            return api_rr()->forbidCommon(trans('messages.user_not_exist'));
        }

        $authUser = $this->getAuthUser();
        $result   = pocket()->user->getUserInfoByUUID($uuid);
        if (!$result->getStatus()) {
            return api_rr()->notFoundUser();
        }
        $user = $result->getData();

        $appendData = [
            'netease'  => ['accid'],
            'wechat'   => ['number'],
            'job',
            'active',
            'distance' => $authUser,
            'number'
        ];
        if (version_compare(user_agent()->clientVersion, '1.6.0', '>=')) {
            $appendData['album'] = $authUser;
        } else {
            $appendData['photo'] = $authUser;
        }
        pocket()->user->appendToUser($user, $appendData);

        return api_rr()->getOK($user);
    }

    /**
     * 获取多个用户
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function multiUser(Request $request)
    {
        $uuidStr = $request->get('uuid');
        if (!$uuidStr) {
            return api_rr()->notFoundUser();
        }
        $uuids  = explode(',', $request->get('uuid'));
        $result = pocket()->user->getUsersInfoByUUIDs($uuids);
        if (!$result->getStatus()) {
            return api_rr()->notFoundUser();
        }
        $users = $result->getData();
        pocket()->user->appendToUsers($users, ['photo', 'netease' => ['accid'], 'wechat' => ['number']]);

        return api_rr()->getOK($users);
    }

    /**
     * 权限表
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     */
    public function eachPowers(Request $request, int $uuid)
    {
        $userId       = $this->getAuthUserId();
        $authUserUUid = $this->getAuthUserUUID();
        $user         = rep()->user->getById($userId);
        $uuid         = (int)$uuid;
        $targetUser   = rep()->user->getByUUid($uuid);
        if (!$targetUser) {
            return api_rr()->notFoundUser();
        }
        $powers         = rep()->userRelation->m()
            ->where('user_id', $user->id)
            ->where('target_user_id', $targetUser->id)
            ->where(function ($query) {
                $query->where('expired_at', 0)->orWhere('expired_at', '>=', time());
            })
            ->get();
        $data['wechat'] = (bool)$powers->where('type', UserRelation::TYPE_LOOK_WECHAT)->first();
        /** 认证女生=>给男的发消息 一路绿灯 */
        $isCharmGirl = pocket()->user->hasRole($user, User::ROLE_CHARM_GIRL);
        if (in_array($uuid, [config('custom.little_helper_uuid')])
            ||
            ($isCharmGirl && $targetUser->gender == User::GENDER_MAN)) {
            $privateChat = true;
        } else {
            $privateChat = (bool)$powers->where('type', UserRelation::TYPE_PRIVATE_CHAT)->first();
        }
        $kefuUUid = rep()->config->m()->where('key', Config::KEY_NETEASE_KF)->value('value');
        /**
         * 客服可以和任何人聊天，任何人也可以和客服聊天
         */
        $unlimitedChatUsersConfig = rep()->config->getQuery()->where('key',
            'unlimited_chat_users')->first();
        if (in_array($kefuUUid, [$authUserUUid, $uuid])
            || ($unlimitedChatUsersConfig
                && in_array($userId, json_decode($unlimitedChatUsersConfig->value, true)))) {
            $privateChat = true;
        }
        $data['private_chat']       = $privateChat;
        $data['follow']             = rep()->userFollow->m()->where('user_id', $user->id)
            ->where('follow_id', $targetUser->id)
            ->exists();
        $data['black']              = rep()->blacklist->m()
            ->where('related_type', Blacklist::RELATED_TYPE_MANUAL)
            ->where('user_id', $user->id)
            ->where('related_id', $targetUser->id)
            ->exists();
        $data['free_unlock_status'] = pocket()->userRelation->hasFreeUnlockOnToday($user);
        $data['free_unlock']        = $data['free_unlock_status']['status'];
        $lock                       = rep()->userRelation->m()
            ->where('user_id', $targetUser->id)
            ->where('target_user_id', $user->id)
            ->where(function ($query) {
                $query->where('expired_at', 0)->orWhere('expired_at', '>=', time());
            })
            ->count();
        $exist                      = rep()->userEvaluate->m()
            ->where('user_id', $userId)
            ->where('target_user_id', $targetUser->id)
            ->count();
        $data['evaluate']           = $isCharmGirl ? (bool)$lock : (bool)$powers->count();
        $data['evaluate']           = $exist > 0 ? false : $data['evaluate'];
        //        $targetIsCharmGirl          = pocket()->user->hasRole($targetUser, User::ROLE_CHARM_GIRL);
        $data['detail_info'] = true;
        if ($targetUser->gender == User::GENDER_WOMEN
            && $user->gender == $targetUser->gender
            && $user->id != $targetUser->id
        ) {
            $data['detail_info'] = false;
        }
        //        $hasTopMoment        = rep()->moment->m()
        //            ->where('user_id', $targetUser->id)
        //            ->where('sort', '>=', 1000)
        //            ->count();
        //        if ($hasTopMoment) {
        //            $data['detail_info'] = false;
        //        }
        $userLock           = rep()->switchModel->m()->where('key', 'lock_wechat')->first();
        $userLockSwitch     = rep()->userSwitch->m()
            ->where('user_id', $targetUser->id)
            ->where('switch_id', $userLock->id)
            ->first();
        $data['is_lock']    = ($userLockSwitch && in_array($userLockSwitch->status,
                [UserSwitch::STATUS_OPEN, UserSwitch::STATUS_ADMIN_LOCK]));
        $data['can_report'] = true;
        $unlock             = rep()->userRelation->isUnlock($user, $targetUser);
        if ($unlock) {
            $existReport = rep()->report->m()
                ->where('user_id', $user->id)
                ->where('related_id', $targetUser->id)
                ->where('related_type', Report::RELATED_TYPE_USER)
                ->where('created_at', '>', strtotime((string)$unlock->created_at))
                ->first();
            if ($existReport) {
                $data['can_report'] = false;
            }
        } else {
            $data['can_report'] = false;
        }
        //        $distance        = pocket()->user->getDistanceUsers($userId, $targetUser->id);
        //        if ($distance == -1) {
        //            $data['is_far'] = true;
        //        } elseif ($distance > 100) {
        //            $data['is_far'] = true;
        //        } else {
        //            $data['is_far'] = false;
        //        }
        $data['is_far'] = false;
        $targetIsBlock  = rep()->blacklist->getBlacklistInfo($targetUser->id, '');
        if ($targetIsBlock) {
            $data['is_admin_black'] = true;
        } else {
            $data['is_admin_black'] = false;
        }

        return api_rr()->getOK($data);
    }

    /**
     * 取消用户真人认证
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function cancelAuthUser(Request $request)
    {
        $userId      = $this->getAuthUserId();
        $user        = rep()->user->getById($userId);
        $userRole    = explode(',', $user->role);
        $newUserRole = array_diff($userRole, ['auth_user']);
        $user->update(['role', implode(',', $newUserRole)]);

        return api_rr()->postOK([]);
    }

    /**
     * 更新用户渠道
     *
     * @param  UserChannelRequest  $request
     *
     * @return JsonResponse
     */
    public function updateChannel(UserChannelRequest $request)
    {
        $reqJson     = request();
        $channelJson = $reqJson->json('channel');
        $traffic     = $reqJson->json('traffic');
        $user        = $this->getAuthUser();
        $userDetail  = rep()->userDetail->getByUserId($user->id);
        if ((time() - $userDetail->created_at->timestamp) > 86400) {
            return api_rr()->postOK('max time');
        }
        if (isset($traffic) && $traffic) {
            $channel = $traffic;
        } else {
            if (!is_json($channelJson)) {
                return api_rr()->forbidCommon(trans('messages.json_parameter_type_error'));
            }
            $reqChannelArr = json_decode($channelJson, true);
            if (!isset($reqChannelArr['channelCode'])) {
                return api_rr()->forbidCommon('没有channelCode');
            }
            $channel = $reqChannelArr['channelCode'];
            if (strpos($channel, ',')) {
                $channelArr = explode(',', $channel);
                $channel    = array_last($channelArr);
            }
        }
        $whetherUpdateChannel  = false;
        $userDetailChannel     = $userDetail->channel;
        $channelHasNatural     = (strripos($channel, 'main') !== false) || (strripos($channel, 'promote') !== false);
        $userChannelHasNatural = (strripos($userDetailChannel, 'main') !== false)
            ||
            (strripos($userDetailChannel, 'promote') !== false);
        if ($userDetailChannel == '') {
            $whetherUpdateChannel = true;
        } elseif ($channel == '') {
            $whetherUpdateChannel = false;
        } elseif (!$channelHasNatural && $userChannelHasNatural) {
            $whetherUpdateChannel = true;
        }
        if ($whetherUpdateChannel) {
            $updateArr = ['channel' => $channel, 'updated_at' => 0];
            if (rep()->userDetail->m()->where('user_id', $user->id)->update($updateArr)) {
                dispatch(new UpdateChannelDataJob("register", $user->id))->onQueue('update_channel_data');

                return api_rr()->postOK('没有channelCode');
            }

            return api_rr()->serviceUnknownForbid(trans('messages.update_channel_error'));

        } else {
            return api_rr()->postOK(trans('messages.update_by_other_channel'));
            //            return api_rr()->forbidCommon('非main渠道禁止修改');
        }
    }

    /**
     * 根据用户手机号搜索用户信息
     *
     * @param  SearchUsersRequest  $request
     *
     * @return JsonResponse
     */
    public function searchUsers(SearchUsersRequest $request)
    {
        $mobile = request('mobile');
        if ($mobile == 0) {
            return api_rr()->notFoundUser();
        }

        $user = rep()->user->m()
            ->select(['id', 'uuid', 'number', 'nickname', 'gender', 'birthday', 'role', 'active_at', 'hide'])
            ->with([
                'userDetail' => function ($query) {
                    $query->select(['user_id', 'intro', 'region', 'height', 'weight', 'region', 'intro']);
                }
            ])->where('destroy_at', 0)->where('mobile', $mobile)->orderBy('id', 'desc')->first();
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        /*** @var $user User */
        pocket()->user->appendToUser($user, ['avatar', 'member', 'auth_user', 'charm_girl']);

        return api_rr()->getOK($user);
    }

    /**
     * 获取单个用户
     *
     * @param  Request  $request
     *
     * @param  int      $uuid  用户uuid
     *
     * @return JsonResponse
     */
    public function webSingleUser(Request $request, int $uuid)
    {
        $user = rep()->user->m()
            ->select(['id', 'uuid', 'number', 'nickname', 'gender', 'birthday', 'role'])
            ->with([
                'userDetail' => function ($query) {
                    $query->select(['user_id', 'intro', 'region']);
                }
            ])
            ->where('uuid', $uuid)->first();
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        /*** @var $user User */
        pocket()->user->appendToUser($user, ['avatar', 'member']);

        return api_rr()->getOK($user);
    }

    /**
     * 剩余查看次数
     *
     * @param  int  $uuid
     *
     * @return JsonResponse
     */
    public function isLook($uuid)
    {
        $userId = $this->getAuthUserId();
        $user   = rep()->user->getById($userId);
        $tUser  = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$user || !$tUser) {
            return api_rr()->forbidCommon('查看一个不存在的用户!');
        }

        $redisKey  = sprintf(config('redis_keys.is_look.key'), $userId);
        $startTime = strtotime(date('Y-m-d'));
        $endTime   = $startTime + 86399;
        $isExist   = redis()->client()->zScore($redisKey, $uuid);
        $count     = redis()->client()->zCount($redisKey, $startTime, $endTime);
        //        $lookArr    = redis()->client()->zRangeByScore($redisKey, $startTime, $endTime, ['withscores' => true]);
        $userMember = rep()->member->m()
            ->where('user_id', $userId)
            ->where(DB::raw('start_at + duration'), '>', time())
            ->first();

        $result['is_looked']    = (($isExist && $isExist >= $startTime && $isExist <= $endTime) || $userMember || $user->gender == User::GENDER_WOMEN);
        $result['count']        = (10 - $count) < 0 ? 0 : (10 - $count);
        $result['alert_status'] = (
            $user->gender == User::GENDER_MAN && !$userMember
            && $result['count'] <= 3
            && !($isExist && $isExist >= $startTime && $isExist <= $endTime)
            && $user->gender != $tUser->gender
        );

        return api_rr()->getOK($result);
    }


    /**
     * 查看个人资料
     *
     * @param  int  $uuid
     *
     * @return JsonResponse
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function look($uuid)
    {
        $userId = $this->getAuthUserId();
        $sUser  = rep()->user->getById($userId);
        $tUser  = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$sUser || !$tUser) {
            return api_rr()->forbidCommon(trans('messages.user_disappear'));
        }

        if ($sUser->gender != $tUser->gender) {
            $redisKey = sprintf(config('redis_keys.is_look.key'), $userId);
            if (count(redis()->client()->zRange($redisKey, 0, -1)) == 0) {
                $cacheKey = sprintf(config('redis_keys.look_block'), $userId);
                $lock     = new RedisLock(Redis::connection(), 'lock:' . $cacheKey, 3);
                $lock->block(3, function () use ($cacheKey, $redisKey, $uuid) {
                    $endTime = strtotime(date('Y-m-d')) + 86400;
                    redis()->client()->zAdd($redisKey, time(), $uuid);
                    redis()->client()->expire($redisKey, $endTime - time());
                });
            } else {
                redis()->client()->zAdd($redisKey, time(), $uuid);
            }
            //todo userVisit表太大,要分表
            $exist = rep()->userVisit->m()
                ->where('user_id', $sUser->id)
                ->where('related_type', UserVisit::RELATED_TYPE_INTRODUCTION)
                ->where('related_id', $tUser->id)
                ->first();
            if ($exist) {
                $exist->update(['visit_time' => time()]);
            } else {
                rep()->userVisit->m()->create([
                    'user_id'      => $sUser->id,
                    'related_type' => UserVisit::RELATED_TYPE_INTRODUCTION,
                    'related_id'   => $tUser->id,
                    'visit_time'   => time()
                ]);
                $userAvatar = rep()->resource->m()->where('related_id', $userId)
                    ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
                    ->first();
                if (!$userAvatar) {
                    return api_rr()->forbidCommon(trans('messages.need_avatar'));
                }
                $isMember = rep()->member->getUserValidMember($tUser->id);
                if ($isMember) {
                    $avatar = cdn_url($userAvatar->resource);
                } else {
                    $avatar = cdn_url($userAvatar->resource) . '?vframe/png/offset/0/h/200|imageMogr2/blur/10x10';
                }
                //                $extension = ['option' => ['badge' => false]];
                $extension   = ['option' => ['push' => false, 'badge' => false]];
                $distance    = pocket()->user->getDistanceUsers($sUser->id, $tUser->id, true);
                $tUserDetail = rep()->userDetail->m()->where('user_id', $tUser->id)->first();
                if (version_compare($tUserDetail->run_version, '2.1.0', '>=')) {
                    $massage = sprintf(trans('messages.visit_my_homepage_tmpl'), $distance);
                    $data    = [
                        'type' => NeteaseCustomCode::USER_VISITED,
                        'data' => ['message' => $massage, 'avatar' => $avatar]
                    ];
                    pocket()->common->commonQueueMoreByPocketJob(
                        pocket()->netease,
                        'msgSendCustomMsg',
                        [config('custom.little_helper_uuid'), $uuid, $data, $extension]
                    );
                }
            }
        }

        return api_rr()->postOK([]);
    }

    /**
     * 私聊
     *
     * @param  int  $uuid
     *
     * @return JsonResponse
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function chat(int $uuid)
    {
        $userId = $this->getAuthUserId();
        $sUser  = rep()->user->getById($userId);
        $tUser  = rep()->user->m()->where('uuid', $uuid)->first();
        if ($sUser->gender != $tUser->gender) {
            $redisKey = sprintf(config('redis_keys.is_chat.key'), $userId);
            if (count(redis()->client()->zRange($redisKey, 0, -1)) == 0) {
                $cacheKey = sprintf(config('redis_keys.chat_block'), $userId);
                $lock     = new RedisLock(Redis::connection(), 'lock:' . $cacheKey, 3);
                $lock->block(3, function () use ($cacheKey, $redisKey, $uuid) {
                    $endTime = strtotime(date('Y-m-d')) + 86400;
                    redis()->client()->zAdd($redisKey, time(), $uuid);
                    redis()->client()->expire($redisKey, $endTime - time());
                });
            } else {
                redis()->client()->zAdd($redisKey, time(), $uuid);
            }
        }

        return api_rr()->postOK([]);
    }

    /**
     * 阅后即焚浏览图片
     *
     * @param  int  $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fire(int $uuid)
    {
        $userId   = $this->getAuthUserId();
        $resource = rep()->resource->m()->where('uuid', $uuid)->first();
        if (!$resource) {
            return api_rr()->forbidCommon(trans('messages.resource_not_exists'));
        }
        $isReal = rep()->userPhoto->m()
            ->where('resource_id', $resource->id)
            ->where('related_type', UserPhoto::RELATED_TYPE_FIRE)
            ->first();
        if (!$isReal || $isReal->status != UserPhoto::STATUS_OPEN) {
            return api_rr()->forbidCommon(trans('messages.picture_not_fire'));
        }
        $isLooked = rep()->userLookOver->m()
            ->where('user_id', $userId)
            ->where('resource_id', $resource->id)
            ->where('expired_at', '>', time())
            ->count();
        if ($isLooked > 0) {
            return api_rr()->forbidCommon(trans('messages.visited_picture'));
        }
        $data = [
            'user_id'     => $userId,
            'target_id'   => $isReal->user_id,
            'resource_id' => $isReal->resource_id,
            'expired_at'  => time() + 86400
        ];
        rep()->userLookOver->m()->create($data);

        return api_rr()->postOK([]);
    }

    /**
     * 获取es一条记录
     *
     * @param $id
     *
     * @return array
     */
    public function getEsDoc($type, $id)
    {
        if ($type === 'moment') {
            $result = pocket()->esMoment->getMomentByMomentId($id);
        } else {
            $result = pocket()->esUser->getUserByUserId($id);
        }

        return $result;
    }

    /**
     * 隐身
     */
    public function userHide()
    {
        $user = rep()->user->getQuery()->select('id', 'uuid', 'gender')
            ->find($this->getAuthUserId());
        $hide = request('hide', 0);
        $hide = $hide == 0 ? User::SHOW : User::HIDE;
        mongodb('user')->where('_id', $user->id)->update(['hide' => $hide]);
        $redisKey = config('redis_keys.hide_users.key');
        if ($hide != 0) {
            redis()->client()->sAdd($redisKey, $user->id);
        } else {
            redis()->client()->sRem($redisKey, $user->id);
        }
        pocket()->esUser->updateUserFieldToEs($user->id, ['hide' => $hide]);
        rep()->user->m()->where('id', $user->id)->update(['hide' => $hide]);

        if (optional($user)->gender == User::GENDER_WOMEN
            && pocket()->coldStartUser->isColdStartUser($user->id)) {
            pocket()->common->clodStartSyncDataByPocketJob(pocket()->coldStartUser,
                'updateColdStartUserSwitches',
                [$user, [SwitchModel::KEY_LOCK_STEALTH => $hide]]);
        }

        return api_rr()->postOK([]);
    }
}

<?php


namespace App\Pockets;

use App\Models\User;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Role;
use App\Foundation\Services\Guzzle\GuzzleHandle;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use App\Models\UserReview;
use App\Models\Wechat;
use App\Constant\NeteaseCustomCode;
use App\Models\Resource;
use App\Models\SwitchModel;
use App\Jobs\UpdateUserInfoToMongoJob;
use App\Models\Blacklist;

class AccountPocket extends BasePocket
{

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
        $midJobs = rep()->userJob->m()->whereIn('user_id', $usersId)->get();
        $jobsId  = $midJobs->pluck('job_id')->toArray();
        $jobs    = rep()->job->getByIds($jobsId, ['id', 'uuid', 'name']);
        foreach ($users as $user) {
            $job = $jobs->whereIn('id', $midJobs->where('user_id', $user->id)->pluck('job_id')->toArray())->first();
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
            return ResultReturn::failed('获取图片详情失败');
        }
    }

    /**
     * 普通用户认证魅力女生后台操作
     *
     * @param $user
     * @param $adminId
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function adminSimpleCharmAuth($user, $adminId)
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
        switch ($userReview->check_status) {
            case UserReview::CHECK_STATUS_DELAY:
            case UserReview::CHECK_STATUS_BLACK_DELAY:
                $action = '魅力女生审核列表';
                break;
            case UserReview::CHECK_STATUS_IGNORE:
            case UserReview::CHECK_STATUS_BLACK_IGNORE:
                $action = '忽略魅力女生审核列表';
                break;
            case UserReview::CHECK_STATUS_FOLLOW_WECHAT:
            case UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE:
                $action = '差一点完成审核的女生';
                break;
        }
        $wechat = rep()->wechat->m()
            ->where('user_id', $user->id)
            ->where('check_status', Wechat::STATUS_DELAY)
            ->first();
        if (!$userReview) {
            return ResultReturn::failed('尚未提交审核资料');
        }
        DB::transaction(function () use (
            $user,
            $userReview,
            $wechat
        ) {
            $userReview->update(['check_status' => UserReview::CHECK_STATUS_PASS, 'done_at' => time()]);
            $wechat->update(['check_status' => Wechat::STATUS_PASS]);
            //            rep()->userJob->m()->where('user_id', $user->id)->delete();
            pocket()->userRole->createUserRole($user, User::ROLE_CHARM_GIRL);
            $job = (new UpdateUserInfoToMongoJob($user->id))->onQueue('update_user_info_to_mongo');
            dispatch($job);
        });
        $message   = '恭喜认证成为魅力女生，你可以主动给男用户发消息。你的微信等私人信息只展示给vip付费用户请放心使用。
注意：请勿主动散播个人联系方式，平台将会对违反者做出封号惩罚。';
        $data      = [
            'type' => NeteaseCustomCode::CHARM_GIRL_AUTH,
            'data' => ['status' => 'pass', 'message' => $message]
        ];
        $extention = ['pushcontent' => $message];
        pocket()->netease->msgSendCustomMsg(config('custom.little_helper_uuid'), $user->uuid, $data, $extention);
        pocket()->tengYu->sendXiaoquanUserContent($user, 'charm_pass');
        pocket()->push->pushToUser($user, '你的魅力女生认证已通过审核，快点查看吧→→');
        pocket()->common->commonQueueMoreByPocketJob(pocket()->stat, 'statUserRegister',
            [$user->id, time(), 'charm_girl'], 10);
        pocket()->gio->report($user->uuid, GIOPocket::EVENT_CHARM_AUTH_PASS, []);
        rep()->operatorSpecialLog->setNewLog($user->uuid, $action, '通过', '', $adminId);

        return ResultReturn::success([]);
    }

    /**
     * 拒绝魅力女生审核
     *
     * @param $user
     * @param $reason
     * @param $adminId
     *
     * @return ResultReturn|\Illuminate\Http\JsonResponse
     * @throws GuzzleException
     */
    public function adminRefuseCharmAuth($user, $reason, $adminId)
    {
        $userReview = rep()->userReview->m()->where('user_id', $user->id)
            ->whereIn('check_status', [
                UserReview::CHECK_STATUS_DELAY,
                UserReview::CHECK_STATUS_BLACK_DELAY,
                UserReview::CHECK_STATUS_IGNORE,
                UserReview::CHECK_STATUS_BLACK_IGNORE,
                UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE,
                UserReview::CHECK_STATUS_FOLLOW_WECHAT
            ])->orderByDesc('id')
            ->first();
        switch ($userReview->check_status) {
            case UserReview::CHECK_STATUS_DELAY:
            case UserReview::CHECK_STATUS_BLACK_DELAY:
                $action = '魅力女生审核列表';
                break;
            case UserReview::CHECK_STATUS_IGNORE:
            case UserReview::CHECK_STATUS_BLACK_IGNORE:
                $action = '忽略魅力女生审核列表';
                break;
            case UserReview::CHECK_STATUS_FOLLOW_WECHAT:
            case UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE:
                $action = '差一点完成审核的女生';
                break;
        }
        $wechat = rep()->wechat->m()->where('user_id', $user->id)
            ->where('check_status', Wechat::STATUS_DELAY)->orderByDesc('id')->first();
        if (!$userReview || !$wechat) {
            return api_rr()->notFoundResult('当前用户不存在审核信息');
        }
        $userReview->update(['check_status' => UserReview::CHECK_STATUS_FAIL, 'reason' => $reason]);
        $wechat->update(['check_status' => Wechat::STATUS_FAIL]);
        $user->update(['role' => 'user']);
        rep()->resource->m()->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)->where('related_id',
            $user->id)->delete();
        rep()->userPhoto->m()->where('user_id', $user->id)->delete();
        $data = ['type' => 2, 'data' => ['status' => 'fail', 'message' => $reason]];
        pocket()->common->sendNimMsgQueueMoreByPocketJob(pocket()->netease, 'msgSendCustomMsg',
            [config('custom.little_helper_uuid'), $user->uuid, $data]
        );
        pocket()->tengYu->sendXiaoquanUserContent($user, 'charm_fail');
        pocket()->push->pushToUser($user, '你的魅力女生认证被拒绝，点击查看原因。');
        rep()->operatorSpecialLog->setNewLog($user->uuid, $action, '拒绝', $reason, $adminId);

        return ResultReturn::success([]);
    }

    /**
     * 处理后台隐身的用户
     *
     * @param $user
     *
     * @return ResultReturn
     */
    public function showUser($user)
    {
        $switch     = rep()->switchModel->m()
            ->where('key', SwitchModel::KEY_ADMIN_HIDE_USER)
            ->first();
        $userSwitch = rep()->userSwitch->m()
            ->where('user_id', $user->id)
            ->where('switch_id', $switch->id)
            ->where('status', $switch->default_status)
            ->first();
        if ($userSwitch) {
            $show = User::SHOW;
            try {
                DB::transaction(function () use ($user, $switch, $show) {
                    rep()->userSwitch->m()
                        ->where('user_id', $switch->id)
                        ->where('switch_id', $switch->id)
                        ->where('status', $switch->default_status)
                        ->update(['status' => !$switch->default_status]);
                    rep()->user->m()->where('id', $switch->id)->update([
                        'hide' => $show
                    ]);
                });
            } catch (\Exception $e) {

                return ResultReturn::failed($e->getMessage());
            }
            $redisKey = config('redis_keys.hide_users.key');
            redis()->client()->sRem($redisKey, $switch->id);
            pocket()->esUser->updateUserFieldToEs($switch->id, ['hide' => $show]);

            if (optional($user)->gender == User::GENDER_WOMEN
                && pocket()->coldStartUser->isColdStartUser($user->id)) {
                pocket()->coldStartUser->updateColdStartUserSwitches($user,
                    [SwitchModel::KEY_ADMIN_HIDE_USER => $show]);
            }
        }

        return ResultReturn::success([]);
    }

    /**
     * 获取所有拉黑用户的微信
     *
     * @return array
     */
    public function getBlockUserWechat()
    {
        $blackUserIds = rep()->blacklist->m()
            ->where('related_type', Blacklist::RELATED_TYPE_OVERALL)
            ->get()
            ->pluck('related_id')
            ->toArray();

        return rep()->wechat->m()
            ->whereIn('user_id', $blackUserIds)
            ->get()
            ->pluck('wechat')
            ->toArray();
    }

    /**
     * 取消魅力女生认证
     *
     * @param $user
     *
     * @return ResultReturn
     */
    public function cancelUserCharm($user)
    {
        $userRole = explode(',', $user->role);
        $charmId  = rep()->role->m()->where('key', 'charm_girl')->first()->id;
        $authId   = rep()->role->m()->where('key', 'auth_user')->first()->id;
        $newRole  = array_intersect($userRole, [Role::KEY_USER, Role::KEY_AUTH_MEMBER]);
        DB::transaction(function () use ($user, $charmId, $authId, $newRole) {
            $user->update(['charm_girl_at' => 0]);
            rep()->userRole->m()
                ->where('user_id', $user->id)
                ->whereIn('role_id', [$charmId, $authId])
                ->delete();
            $user->update(['role' => implode(',', $newRole)]);
            rep()->userReview->m()
                ->where('user_id', $user->id)
                ->delete();
            rep()->wechat->m()
                ->where('user_id', $user->id)
                ->delete();
            rep()->userPhoto->m()->where('user_id', $user->id)->delete();
            rep()->resource->m()->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)->where('related_id',
                $user->id)->delete();
            mongodb('user')->where('_id', $user->id)->update([
                'charm_girl' => 0
            ]);
            $job = (new UpdateUserInfoToMongoJob($user->id))->onQueue('update_user_info_to_mongo');
            dispatch($job);
        });
        pocket()->common->commonQueueMoreByPocketJob(pocket()->stat, 'statCharmGirlCancel',
            [$user->id], 10);

        pocket()->esUser->updateUserFieldToEs($user->id, [
            'charm_girl'         => 0,
            'charm_girl_done_at' => 0,
        ]);

        return ResultReturn::success([]);
    }
}

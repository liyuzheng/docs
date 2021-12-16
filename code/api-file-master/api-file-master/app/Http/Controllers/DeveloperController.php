<?php


namespace App\Http\Controllers;

use App\Constant\NeteaseCustomCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Constant\ApiBusinessCode;
use App\Foundation\Handlers\RoutePrefixParse;
use Illuminate\Support\Facades\Redis;

/**
 * 开发使用的控制器
 * Class DeveloperController
 * @package App\Http\Controllers
 */
class DeveloperController extends BaseController
{
    /**
     * 所有的测试方法列表
     * @throws \ReflectionException
     */
    public function index()
    {
        return (new RoutePrefixParse(request('type', 'developer')))->getRouteListHtml();
    }

    /**
     * 获取最新的短信
     *
     * @return JsonResponse
     */
    public function sms()
    {
        $sms = rep()->sms->m()
            ->select(['id', 'mobile', 'code'])
            ->orderByDesc('created_at')->limit(6)->get()->toArray();

        return api_rr()->getOK($sms);
    }


    /**
     * 发送审核通过消息
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function messagesAuditPass(Request $request, $uuid)
    {
        $user = rep()->user->getByUUid($uuid);
        if (!$user) {
            return api_rr()->notFoundUser('用户不存在');
        }
        $message  = '恭喜你认证完成~';
        $data     = ['type' => 2, 'data' => ['status' => 'pass', 'message' => $message]];
        $response = pocket()->netease->msgSendCustomMsg(config('custom.little_helper_uuid'), $uuid, $data);

        return api_rr()->getOK($response);
    }

    /**
     * 发送审核失败消息
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function messagesAuditFailed(Request $request, $uuid)
    {
        $user = rep()->user->getByUUid($uuid);
        if (!$user) {
            return api_rr()->notFoundUser('用户不存在');
        }
        $message  = '认证失败~';
        $data     = ['type' => 2, 'data' => ['status' => 'fail', 'message' => $message]];
        $response = pocket()->netease->msgSendCustomMsg(config('custom.little_helper_uuid'), $uuid, $data);

        return api_rr()->getOK($response);
    }


    /**
     * 发送云信消息
     *
     * @param  Request  $request
     * @param           $sUUid
     * @param           $rUUid
     *
     * @return JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendMessage(Request $request, $sUUid, $rUUid)
    {
        $response = pocket()->netease->msgSendMsg($sUUid, $rUUid, 'hi');

        return api_rr()->getOK($response->getData(), $response->getMessage());
    }

    /**
     * 测试强制更新
     *
     * @return JsonResponse
     */
    public function forcedToUpdate()
    {
        $response = [
            'code'    => ApiBusinessCode::FORCED_TO_UPDATE,
            'message' => '尊敬的用户，本次更新了新的声优模块，在提升用户体验的同时，本次更新将为强制更新',
            'data'    => [
                'latest_version' => '3.9.0',
                'redirect_url'   => 'https://share.ruanruan.club/my91/vip',
            ]
        ];

        return response()->json($response, 570, []);
    }

    /**
     * 根据UUID获得用户Auth-Token
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return JsonResponse
     */
    public function getAuthToken(Request $request, $uuid)
    {
        $userResp = pocket()->user->getUserInfoByUUID($uuid);
        if (!$userResp->getStatus()) {
            return api_rr()->notFoundUser('找不到用户');
        }
        $user      = $userResp->getData();
        $authToken = pocket()->user->getUserToken($user);

        return api_rr()->getOK([
            'auth_token' => $authToken
        ]);
    }

    /**
     * 发送一个带图标的弹框通知
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function iconNotice(Request $request, $uuid)
    {
        $body = [
            'type' => NeteaseCustomCode::ALERT_IMAGE_MESSAGE,
            'data' => [
                'show_type' => 1,
                'icon'      => cdn_url('uploads/common/diamond_refund.png'),
                'message'   => '恭喜你自己给自己发了一个消息, 牛逼不牛逼！',
            ]
        ];

        $sender   = pocket()->common->getSystemHelperByAppName('common');
        $response = pocket()->netease->msgSendCustomMsg($sender, $uuid, $body,
            ['option' => ['push' => false, 'badge' => false]]);

        return api_rr()->getOK($response->getData(), $response->getMessage());
    }

    /**
     * 更新config
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function updateConfig(Request $request)
    {
        $key   = $request->get('key');
        $value = $request->get('value');
        rep()->config->m()->where('key', $key)->update(['value' => $value]);
        $value = rep()->config->m()->where('key', $key)->first();

        return api_rr()->getOK([
            'key'   => $key,
            'value' => $value
        ]);
    }

    /**
     * 更新用户的邀请AB类型, 201:A类 202:B类 参数?type=201
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return JsonResponse
     */
    public function createUserInviteTest(Request $request, $uuid)
    {
        $user             = rep()->user->getQuery()->where('uuid', $uuid)->first();
        $inviteTestRecord = rep()->userAb->getUserInviteTestRecord($user);
        $updateType       = $request->query('type', 201);
        if (!$inviteTestRecord) {
            rep()->userAb->getQuery()->create(['user_id' => $user->id, 'type' => $updateType]);
        } elseif ($inviteTestRecord->getRawOriginal('type') != $updateType) {
            $inviteTestRecord->update(['type' => $updateType]);
        }

        rep()->statRemainLoginLog->getQuery()->where('user_id', $user->id)
            ->forceDelete();
        $redisKey = sprintf(config('redis_keys.cache.has_remained'), $user->id);
        Redis::exists($redisKey) && Redis::del($redisKey);

        return api_rr()->getOK([], '更新完成');
    }

    /**
     * 增加某个用户活跃天数 参数?days=1
     *
     * @param  Request  $request
     * @param           $uuid
     *
     * @return JsonResponse
     */
    public function addUserLoginDays(Request $request, $uuid)
    {
        $user    = rep()->user->getQuery()->where('uuid', $uuid)->first();
        $addDays = $request->query('days', 1);
        rep()->statRemainLoginLog->getQuery()->where('user_id', $user->id)
            ->forceDelete();

        $userRegAt  = $user->getRawOriginal('created_at');
        $startAt    = strtotime('-' . ($addDays + 1) . ' days');
        $insertData = [];
        for ($i = 0; $i < $addDays; $i++) {
            $insertData[] = [
                'user_id'     => $user->id,
                'os'          => 100,
                'login_at'    => $startAt,
                'remain_day'  => 1,
                'register_at' => $userRegAt,
                'created_at'  => $startAt,
                'updated_at'  => $startAt
            ];

            $startAt += 86400;
        }

        rep()->statRemainLoginLog->getQuery()->insert($insertData);
        $redisKey = sprintf(config('redis_keys.cache.has_remained'), $user->id);
        redis()->client()->exists($redisKey) && redis()->client()->del($redisKey);
        $authRedisKey = config('redis_keys.auth.user_login_at.key');
        redis()->client()->exists($authRedisKey) && redis()->client()->del($authRedisKey);

        return api_rr()->getOK([], '更新完成');
    }


    /**
     * 根据用户昵称获取用户信息 url 传递nickname
     *
     * @param  Request  $request
     * @param  string  nickname
     *
     * @return JsonResponse
     */
    public function getUserByName(Request $request)
    {
        $nickname = request('nickname');
        $user     = rep()->user->m()->where('nickname', 'like', "%$nickname%")->get();

        return api_rr()->getOK($user);
    }

    /**
     * 同步冷起的魅力女生
     *
     * @param $uuid
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function syncColdStartUser($uuid)
    {
        $user = rep()->user->getQuery()->where('uuid', $uuid)->first();
        pocket()->user->syncColdStartUser($user->id);

        return api_rr()->getOK([]);
    }
}

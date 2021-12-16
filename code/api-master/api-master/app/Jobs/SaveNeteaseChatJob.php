<?php

namespace App\Jobs;

use App\Foundation\Handlers\Tools;
use App\Models\UnlockPreOrder;
use Carbon\Carbon;
use App\Models\Sms;
use App\Models\User;
use GuzzleHttp\Client;
use App\Pockets\EsPocket;
use App\Models\UserRelation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Pockets\GIOPocket;

class SaveNeteaseChatJob extends Job
{
    private const BJ_APP_NAME = 'bojinquan';
    protected $callBackData;

    public function __construct(array $callBackData)
    {
        $this->callBackData = $callBackData;
    }

    public function handle()
    {
        $fromUserUUID = $this->callBackData['fromAccount'];
        $toUserUUID   = $this->callBackData['to'];
        $users        = rep()->user->getByUUids([$fromUserUUID, $toUserUUID]);
        $fromUser     = $users->where('uuid', $fromUserUUID)->first();
        $toUser       = $users->where('uuid', $toUserUUID)->first();
        if (!$fromUser || !$toUser) {
            return;
        }

        $this->sendJPushMsgToIos($fromUser->id, $toUser->id);
        $this->sendCharmGirlMsg($fromUser, $toUser);
        $this->saveChatToEs($fromUser, $toUser);
        $this->sendAntiSpamMsg($fromUser, $toUser);

        if ($toUser->getRawOriginal('appname') == self::BJ_APP_NAME) {
            $api = config('custom.internal.cold_start_message_cc_url');
            Tools::getHttpRequestClient()->post($api,
                ['json' => $this->callBackData, 'headers' => ['Host' => 'api.okacea.com']]);
        }

        if ($fromUser->getRawOriginal('appname') != self::BJ_APP_NAME) {
            $blockResponse = $this->blockUser($fromUser->id, $toUser->id);
            $this->checkUsersUnlockPreOrder($fromUser, $toUser);
        }
    }

    /**
     * 检查用户之间未完成的解锁订单
     *
     * @param  User  $from
     * @param  User  $to
     */
    public function checkUsersUnlockPreOrder(User $from, User $to)
    {
        $preOrder = rep()->unlockPreOrder->getQuery()->where(function ($query) use ($from, $to) {
            $query->where(function ($query) use ($from, $to) {
                $query->where('user_id', $from->id)->where('target_user_id', $to->id);
            })->orWhere(function ($query) use ($from, $to) {
                $query->where('user_id', $to->id)->where('target_user_id', $from->id);
            });
        })->where('expired_at', '>', time())->where('done_at', 0)->first();

        if ($preOrder) {
            DB::transaction(function () use ($preOrder, $from, $to) {
                $order = rep()->unlockPreOrder->getQuery()->lockForUpdate()->find($preOrder->id);
                if ($order->getRawOriginal('status') != UnlockPreOrder::STATUS_REFUND
                    && !$order->getRawOriginal('done_at')) {
                    $currentNow = time();
                    $updateData = [];

                    if ($order->getRawOriginal('user_id') == $from->id) {
                        if (!$order->getRawOriginal('user_trigger_at')) {
                            $updateData = [
                                'user_trigger_at' => $currentNow,
                                'expired_at'      => UnlockPreOrder::getExpiredAt($currentNow)
                            ];
                        }
                    } else {
                        if (!$order->getRawOriginal('t_user_trigger_at')) {
                            $updateData = ['t_user_trigger_at' => $currentNow, 'done_at' => $currentNow];
                        }
                    }

                    $updateData && $order->update($updateData);
                }
            });
            pocket()->gio->report($from->uuid, GIOPocket::EVENT_PRIVATE_CHAT_SUCCESS, ['eachOtherID_var' => $to->uuid]);
        }
    }

    /**
     * 极光发送消息
     *
     * @param  int  $sendUserId
     * @param  int  $receiveUserId
     *
     * @return ResultReturn
     */
    public function sendJPushMsgToIos(int $sendUserId, int $receiveUserId)
    {
        if ($this->callBackData['msgType'] === 'TEXT') {
            $body = $this->callBackData['body'];
        } else {
            return ResultReturn::failed('not text');
        }
        $response = pocket()->common->commonQueueMoreByPocketJob(
            pocket()->push,
            'pushToNotPushIosUser',
            ['chat_msg', $sendUserId, $receiveUserId, $body]
        );
        $job      = (new SendWeChatTemplateMsgJob(
            pocket()->notify,
            'sendToChatMsgToWeChatTemplate',
            [$sendUserId, $receiveUserId, $body, (int)($this->callBackData['msgTimestamp'] / 1000)])
        )->onQueue('send_wechat_template_msg');
        dispatch($job);

        return $response;
    }

    /**
     * 给不活跃的魅力女生发送短信
     *
     * @param $fromUser
     * @param $toUser
     *
     * @return bool
     */
    public function sendCharmGirlMsg($fromUser, $toUser)
    {
        //魅力女生活跃过不发短信
        $oneTime = Carbon::today()->timestamp + 13 * 60 * 60;
        if (time() >= $oneTime) {
            if ($toUser->active_at >= Carbon::today()->timestamp) {
                return true;
            }
        } else {
            return true;
        }
        $now               = time();
        $isCharmGirl       = pocket()->user->hasRole($toUser, User::ROLE_CHARM_GIRL);
        $isMan             = $fromUser->gender === User::GENDER_MAN;
        $hasSendFiveMinute = rep()->sms->m()
            ->where('user_id', $toUser->id)
            ->where('type', Sms::TYPE_NOT_ACTIVE_CHARM_GIRL)
            ->where('created_at', '>=', $now - 5 * 60)
            ->count();
        /** 最近5分钟发过则不发 */
        if ($hasSendFiveMinute > 0) {
            return true;
        }

        $sendTimes = rep()->sms->m()
            ->where('user_id', $toUser->id)
            ->where('type', Sms::TYPE_NOT_ACTIVE_CHARM_GIRL)
            ->where('created_at', '>=', Carbon::today()->timestamp)
            ->count();
        if ($isCharmGirl && $isMan && $sendTimes < 3) {
            try {
                pocket()->tengYu->sendXiaoquanUserContent($toUser, 'active_charm');
            } catch (GuzzleException $e) {
                Log::error($e->getMessage());

                return true;
            }
            rep()->sms->m()->create([
                'user_id'    => $toUser->id,
                'type'       => Sms::TYPE_NOT_ACTIVE_CHARM_GIRL,
                'mobile'     => $toUser->mobile,
                'code'       => 0,
                'client_id'  => 0,
                'expired_at' => $now,
                'used_at'    => $now,
            ]);
        }
    }

    /**
     * 保存到es
     *
     * @param  User  $fromUser
     * @param  User  $toUser
     *
     * @return bool
     */
    public function saveChatToEs(User $fromUser, User $toUser): bool
    {
        $userUidArr = [$fromUser->id, $toUser->id];
        asort($userUidArr);
        $sendId    = (int)$fromUser->id;
        $receiveId = (int)$toUser->id;
        $type      = 0;
        $body      = "";
        if ($this->callBackData['msgType'] === 'TEXT') {
            $type = EsPocket::TYPE_TEXT;
            $body = $this->callBackData['body'];
        } elseif ($this->callBackData['msgType'] === 'AUDIO') {
            $type   = EsPocket::TYPE_AUDIO;
            $attach = json_decode($this->callBackData['attach'], true);
            $url    = $attach['url'] ?? "";
            if ($url) {
                $body = $this->saveToRemoteUrl($url, 'aac');
            }
        } elseif ($this->callBackData['msgType'] === 'CUSTOM') {
            $attach = json_decode($this->callBackData['attach'], true);
            if (isset($attach['type']) && $attach['type'] == 99) {
                $type = EsPocket::TYPE_IMAGE;
                $url  = $attach['data']['url'] ?? "";
                if ($url) {
                    $body = $this->saveToRemoteUrl($url, 'png');
                    //                    $sendUser = rep()->user->m()->select(['uuid'])->where('id', $sendId)->first();
                    //                    pocket()->common->commonQueueMoreByPocketJob(
                    //                        pocket()->neteaseDun,
                    //                        'checkImages',
                    //                        [[$url], config('netease.keys.dun.chat_pic'), $sendUser->uuid]
                    //                    );
                }
            }
        }
        if (!$type) {
            return false;
        }
        $data = [
            'scene'      => EsPocket::SCENE_CHAT,
            'scene_id'   => 0,
            'send_id'    => $sendId,
            'receive_id' => $receiveId,
            'group'      => implode('_', $userUidArr),
            'content'    => $body,
            'body'       => $this->callBackData,
            'type'       => $type,
            'send_at'    => (int)$this->callBackData['msgTimestamp'],
            'created_at' => time()
        ];

        pocket()->esImChat->postUserChatInfo($data);
        if ($type === EsPocket::TYPE_TEXT) {
            pocket()->esImChat->saveSpamMassage($data);
        }

        return true;
    }

    /**
     * 保存图片到远程地址
     *
     * @param $url
     * @param $ext
     *
     * @return false|JsonResponse|mixed|string
     */
    public function saveToRemoteUrl($url, $ext)
    {
        $fileUrl = file_url("file/remote/single");
        try {
            $client   = new Client();
            $response = $client->post($fileUrl, [
                'json' => [
                    'url' => $url,
                    'ext' => $ext
                ]
            ]);
            if ($response->getStatusCode() !== 200) {

                return "";
            }
        } catch (GuzzleException $e) {
            return "";
        }
        $result = json_decode($response->getBody()->getContents(), true);

        return $result['data']['resource'] ?? "";
    }

    /**
     * 拉黑用户
     *
     * 1. 男生给男生发消息
     * 2. 男生给未解锁过的女生发消息
     * 3.非魅力女生给男生发消息
     * 4.魅力女生给魅力女生发消息
     *
     * @param  int  $sendUserId
     * @param  int  $receiveUserId
     */
    public function blockUser(int $sendUserId, int $receiveUserId)
    {
        $breakUserId = pocket()->blacklist->getBreakBlockUserId();
        //如果是官方客服就跳过
        if (in_array($sendUserId, $breakUserId) || in_array($receiveUserId, $breakUserId)) {
            return ResultReturn::failed('break');
        }

        $reason        = '自动封禁: 违规发送消息';
        $users         = rep()->user->getByIds([$sendUserId, $receiveUserId]);
        $sendUser      = $users->find($sendUserId);
        $receiveUser   = $users->find($receiveUserId);
        $sendUserRoles = $receiveUserRoles = [];
        foreach (explode(',', $sendUser->getOriginal('role')) as $item) {
            $sendUserRoles[$item] = $item;
        }
        foreach (explode(',', $receiveUser->getOriginal('role')) as $item) {
            $receiveUserRoles[$item] = $item;
        }
        $sendUserGender    = $sendUser->getOriginal('gender');
        $receiveUserGender = $receiveUser->getOriginal('gender');
        //发送方是男生
        if ($sendUserGender === User::GENDER_MAN) {
            //男生给男生发消息
            if ($receiveUserGender === User::GENDER_MAN) {
                return $this->postBlockUser($sendUserId, $receiveUserId, $reason, '男生给男生发消息');
            }

            //男生给未解锁过的女生发消息
            $chatPower = rep()->userRelation->write()
                ->where('user_id', $sendUserId)
                ->where('target_user_id', $receiveUserId)
                ->where('type', UserRelation::TYPE_PRIVATE_CHAT)
                //                ->where(function ($query) {
                //                    $query->where('expired_at', 0)->orWhere('expired_at', '>=', time());
                //                })
                ->orderBy('id', 'desc')
                ->first();
            if (!$chatPower && $sendUser->appname != self::BJ_APP_NAME) {
                return $this->postBlockUser($sendUserId, $receiveUserId, $reason, '男生给未解锁过的女生发消息');
            }
        }

        //发送方是女生
        if ($sendUserGender === User::GENDER_WOMEN) {
            //非魅力女生给男生发消息
            if (!array_key_exists(User::ROLE_CHARM_GIRL, $sendUserRoles) && $receiveUserGender === User::GENDER_MAN) {

                return $this->postBlockUser($sendUserId, $receiveUserId, $reason, '非魅力女生给男生发消息');
            }
            //魅力女生给魅力女生发消息
            if (array_key_exists(User::ROLE_CHARM_GIRL, $sendUserRoles)
                &&
                array_key_exists(User::ROLE_CHARM_GIRL, $receiveUserRoles)) {
                return $this->postBlockUser($sendUserId, $receiveUserId, $reason, '魅力女生给魅力女生发消息');
            }
        }
    }

    /**
     * @param  int     $sendUserId
     * @param  int     $receiveUserId
     * @param  string  $reason
     * @param  string  $remark
     *
     * @return ResultReturn
     */
    public function postBlockUser(int $sendUserId, int $receiveUserId, string $reason, string $remark): ResultReturn
    {
        $response   = pocket()->blacklist->postGlobalBlockUser($sendUserId, $reason, $remark, 0);
        $ids        = $response->getData()['ids'];
        $returnData = [
            'send_user_id' => $sendUserId,
            'reason'       => $reason,
            'remark'       => $remark,
            'ids'          => $ids
        ];
        pocket()->common->commonQueueMoreByPocketJob(
            pocket()->dingTalk,
            'sendSimpleMessage',
            [
                'https://oapi.dingtalk.com/robot/send?access_token=c8d84555a2b745d5786b021c7ea7c3effb7bf09537e2d07bc49779aade28d07c',
                sprintf('[自动封禁: 违规发送消息]-, ids: %s s_user:%s r_user:%s remark: %s data: %s',
                    implode(',', $ids),
                    $sendUserId,
                    $receiveUserId,
                    $remark,
                    json_encode($returnData)
                )
            ]
        );
        pocket()->common->commonQueueMoreByPocketJob(
            pocket()->user,
            'recallUserMsg',
            [$sendUserId]
        );

        return ResultReturn::success($returnData);
    }

    /**
     * @param $fromUser
     * @param $toUser
     */
    public function sendAntiSpamMsg($fromUser, $toUser)
    {
        if (key_exists('antispam', $this->callBackData)) {
            $antiSpam = $this->callBackData['antispam'];
            if ($antiSpam == 'true') {
                pocket()->common->commonQueueMoreByPocketJob(
                    pocket()->netease,
                    'sendChatTips',
                    [config('custom.little_helper_uuid'), $fromUser->uuid, $fromUser->uuid, '图片涉嫌违规，发送失败']
                );
            }
        }
    }
}

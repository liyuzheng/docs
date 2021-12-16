<?php


namespace App\Http\Controllers;


use App\Models\MemberRecord;
use App\Models\TradePay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\UpdateChannelDataJob;
use App\Jobs\SaveNeteaseChatJob;
use App\Models\ResourceCheck;
use App\Models\Resource;
use App\Models\User;
use Mockery\Exception;
use App\Models\UserPhoto;
use WGCYunPay\Util\StringUtil;
use App\Models\Repay;
use App\Models\UserReview;
use App\Models\TradeWithdraw;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\SmsAd;
use App\Jobs\UpdateUserInfoToMongoJob;
use App\Constant\NeteaseCustomCode;
use App\Models\Good;
use App\Pockets\GIOPocket;

class CallBackController extends BaseController
{
    /**
     * ping++ 回调
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pingXXPay(Request $request)
    {
        $callbackData = $request->all();
        $verifyResp   = pocket()->tradePay->verifyPingXxCallbackParams($callbackData);
        if (!$verifyResp->getStatus()) {
            return api_rr()->forbidCommon($verifyResp->getMessage());
        } elseif (is_null($verifyResp->getData())) {
            return api_rr()->postOK($verifyResp->getMessage());
        }

        $tradePay     = $verifyResp->getData();
        $user         = rep()->user->getQuery()->find($tradePay->user_id);
        $tradePayResp = pocket()->tradePay->processPingXxOrder($user, $tradePay, $callbackData);
        if ($tradePayResp->getStatus()) {
            pocket()->common->commonQueueMoreByPocketJob(pocket()->tradePay, 'tradePayDoneNotice',
                [$user->id, $tradePayResp->getData()->good_id]);
        }

        return api_rr()->postOK((object)[]);
    }

    /**
     * 苹果支付回调
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function applePay(Request $request)
    {
        $callbackData = $request->all();

        $verifyResp = pocket()->tradePay->verifyAppleCallbackParams($callbackData);
        if (!$verifyResp->getStatus()) {
            return api_rr()->forbidCommon($verifyResp->getMessage());
        }

        switch ($callbackData['notification_type']) {
            case 'REFUND':
                $order = $callbackData['latest_receipt_info'];
                pocket()->member->cancelMemberByOirTradeNo($order, $callbackData['notification_type']);
                break;
            case 'RENEWAL':
                if (isset($callbackData['unified_receipt'])) {
                    $oriTradeNo   = $callbackData['latest_receipt_info']['original_transaction_id'];
                    $memberRecord = rep()->memberRecord->getQuery()->where('certificate', $oriTradeNo)
                        ->whereIn('status', MemberRecord::STATUS_VALID)->orderBy('id', 'desc')->first();
                    if ($memberRecord) {
                        $evidence = $callbackData['unified_receipt']['latest_receipt'];
                        $password = $callbackData['password'];
                        pocket()->tradePay->processAppleOrdersByMember($memberRecord,
                            $evidence, $password);
                    }
                }
                break;
        }

        return api_rr()->postOK([]);
    }

    /**
     * 云信回调
     * @return mixed
     */
    public function callbackNimServer()
    {
        $callBackData = request()->all();
        //聊天室队列操作事件抄送
        if (isset($callBackData['eventType'])) {
            switch ($callBackData['eventType']) {
                //会话类型消息抄送
                case 1:
                    if ($callBackData['msgType'] === 'TEXT' || $callBackData['msgType'] === 'AUDIO') {
                        $saveNeteaseChatJob = (new SaveNeteaseChatJob($callBackData))
                            ->onQueue('save_netease_chat');
                        dispatch($saveNeteaseChatJob);

                        return api_rr()->postOK([]);
                    }
                    if ($callBackData['msgType'] === 'CUSTOM') {
                        $attach = json_decode($callBackData['attach'], true);
                        if (isset($attach['type']) && $attach['type'] == 99) {
                            $saveNeteaseChatJob = (new SaveNeteaseChatJob($callBackData))
                                ->onQueue('save_netease_chat');
                            dispatch($saveNeteaseChatJob);
                        }

                        return api_rr()->postOK([]);
                    }
                    break;
                case 4:
                    break;
                default:
                    return api_rr()->postOK([]);
                    break;
            }

        }

        return api_rr()->postOK([]);
    }

    /**
     * 数美视频鉴黄回调
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function callbackFengkongVideo(Request $request)
    {
        $data         = $request->all();
        $result       = json_decode($data['result']);
        $checkPicData = [];
        $now          = time();
        $passCount    = 0;
        $compareCount = 0;
        $uuid         = $result->btId;
        $video        = rep()->resource->m()->where('uuid', $uuid)->first();
        if (!$video) {
            return api_rr()->postOK([]);
        }
        if ($result->code == 1100) {
            $user    = rep()->user->getById($video->related_id);
            $basePic = pocket()->account->getBasePic($video->related_id);
            if ($video && count($result->detail) > 0) {
                foreach ($result->detail as $item) {
                    $riskLevel = $item->riskLevel;
                    if ($riskLevel == 'REJECT') {
                        DB::transaction(function () use ($video, $now) {
                            rep()->resource->getById($video->id)->delete();
                            rep()->resourceCheck->m()->where('resource_id', $video->id)
                                ->update(['status' => ResourceCheck::STATUS_PORN_FAIL, 'deleted_at' => $now]);
                            rep()->userPhoto->m()->where('resource_id', $video->id)->delete();
                        });
                        $message = trans('messages.video_unhealthy_notice', [], $user->language);
                        pocket()->common->sendNimMsgQueueMoreByPocketJob(
                            pocket()->netease,
                            'msgSendMsg',
                            [config('custom.little_helper_uuid'), $user->uuid, $message]
                        );

                        return api_rr()->postOK([]);
                    } else {
                        $passCount++;
                    }
                    try {
                        $image      = $item->imgUrl;
                        $faceResult = pocket()->aliYun->getCompareResponse(
                            $basePic,
                            $image
                        );
                        if ($faceResult->getMessage() != 'No face detected from given images' &&
                            $faceResult->getData() &&
                            key_exists('SimilarityScore', $faceResult->getData()) &&
                            $faceResult->getData()['SimilarityScore'] > User::ALIYUN_TEST_THRESHOLD) {
                            $compareCount++;
                        }
                    } catch (Exception $e) {
                        logger()->warning($e->getMessage());
                    }

                    $checkPicData[] = [
                        'bt_id'      => $uuid,
                        'url'        => $item->imgUrl,
                        'risk_level' => $item->riskLevel,
                        'request_id' => $item->requestId,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
                rep()->fengkongCheckPic->m()->insert($checkPicData);
                $resourceCheck = rep()->resourceCheck->m()->where('resource_id', $video->id)->first();
                if ($passCount == count($result->detail) && $compareCount > 0) {
                    DB::transaction(function () use ($resourceCheck, $video, $user) {
                        $video->update(['resource' => $resourceCheck->resource, 'type' => Resource::TYPE_VIDEO]);
                        $resourceCheck->update(['status' => ResourceCheck::STATUS_PASS]);
                        rep()->userPhoto->m()->where('resource_id',
                            $video->id)->update(['status' => UserPhoto::STATUS_OPEN]);
                        $job = (new UpdateUserInfoToMongoJob($user->id))->onQueue('update_user_info_to_mongo');
                        dispatch($job);
                    });
                    $message = trans('messages.video_detect_pass', [], $user->language);
                } else {
                    DB::transaction(function () use ($resourceCheck, $video, $now) {
                        $video->delete();
                        $resourceCheck->update(['status' => ResourceCheck::STATUS_FACE_FAIL, 'deleted_at' => $now]);
                        rep()->userPhoto->m()->where('resource_id', $video->id)->delete();
                    });
                    $message = trans('messages.video_detect_not_pass', [], $user->language);
                    if ($passCount != count($result->detail)) {
                        $message = trans('messages.video_unhealthy_notice', [], $user->language);
                    } elseif ($compareCount == 0) {
                        $message = trans('messages.video_face_detect_not_pass', [], $user->language);
                    }
                }
                pocket()->common->sendNimMsgQueueMoreByPocketJob(
                    pocket()->netease,
                    'msgSendMsg',
                    [config('custom.little_helper_uuid'), $user->uuid, $message]
                );
            }
        } else {
            $user = rep()->user->getById($video->related_id);
            $video->delete();
            rep()->userPhoto->m()->where('resource_id', $video->id)->delete();
            rep()->resourceCheck->m()->where('resource_id', $video->id)->delete();
            $message = trans('messages.video_detect_failed', [], $user->language);
            pocket()->common->sendNimMsgQueueMoreByPocketJob(pocket()->netease,
                'msgSendMsg', [config('custom.little_helper_uuid'), $user->uuid, $message]);
        }

        return api_rr()->postOK([]);
    }

    /**
     * 云账户回调
     *
     * @param  Request  $request
     */
    public function wgc(Request $request)
    {
        $notifyData = request()->all();
        logger()->setLogType('wgc_callback_notify_data')->error(json_encode($notifyData));
        [$result, $verify] = pocket()->wgcYunPay->verify($notifyData);
        logger()->setLogType('wgc_callback_result')->error(json_encode([$result, $verify]));
        $orderId = $result['data']['order_id'] ?? 0;
        $status  = $result['data']['status'] ?? Repay::STATUS_FAIL;
        if ($orderId) {
            $repay = rep()->repay->m()->where('order_id', $orderId)->first();
            if ($repay) {
                try {
                    DB::transaction(function () use ($repay, $result, $notifyData, $status) {
                        rep()->repayData->m()->create([
                            'repay_id'          => $repay->id,
                            'callback'          => json_encode($result),
                            'callback_original' => json_encode($notifyData)
                        ]);
                        rep()->repay->m()->where('id', $repay->id)->update([
                            'status'     => $status == 1 ? Repay::STATUS_SUCCESS : Repay::STATUS_FAIL,
                            'ori_status' => $status
                        ]);
                        rep()->tradeWithdraw->m()
                            ->where('id', $repay->related_id)
                            ->where('repay_status', '!=', TradeWithdraw::STATUS_SUCCESS)
                            ->update([
                                'repay_status' => $status == 1 ? TradeWithdraw::STATUS_SUCCESS : TradeWithdraw::STATUS_FAIL,
                            ]);
                    });
                } catch (\Exception $exception) {
                    logger()->setLogType('wgc_callback_error')->error(
                        json_encode([$repay, $result, $notifyData, $status]));
                }
                //大额打款报警机制
                if (isset($result['data']['pay']) && $result['data']['pay'] >= 500) {
                    pocket()->common->commonQueueMoreByPocketJob(pocket()->dingTalk, 'sendSimpleMessage', [
                        'https://oapi.dingtalk.com/robot/send?access_token=c8d84555a2b745d5786b021c7ea7c3effb7bf09537e2d07bc49779aade28d07c',
                        sprintf('[云账户打款支付回调]-大额打款通知,提现id：%s ;已打款金额 %s ;callback: %s',
                            $repay->order_id, $result['data']['pay'] ?? 0, json_encode($result))
                    ]);
                }

                return 'success';
            }
        } else {
            logger()->setLogType('wgc_callback_order_id_error')->error(json_encode([$result, $notifyData, $status]));
        }
    }

    /**
     * 微信公众号回调
     *
     * @param  Request  $request
     */
    public function callbackWeChatMp(Request $request)
    {
        $parameters = pocket()->util->xmlToArray($request->getContent());
        if ($parameters['MsgType'] === 'event') {
            switch ($parameters['Event']) {
                case 'subscribe':
                    if (isset($parameters['Ticket']) && $parameters['Ticket']) {
                        $resp = pocket()->userFollowOffice->postBindUserByTicket(
                            $parameters['Ticket'],
                            $parameters['FromUserName']
                        );
                        if ($resp->getStatus()) {
                            pocket()->userSwitch->postSyncPushTemMsgStateByFollow($resp->getData()['user_id'], true);

                            return pocket()->notify->sendWeChatSubscribeMsgByType($resp->getData()['push_msg_type']);
                        } else {
                            return pocket()->notify->weChatSubscribeNormalMsg();
                        }

                    } else {
                        if (isset($parameters['FromUserName']) && $parameters['FromUserName']) {
                            $userAuth = rep()->userAuth->getByWeChatOfficeOpenId($parameters['FromUserName']);
                            if ($userAuth) {
                                pocket()->userFollowOffice->postOfficeBindByUserId($userAuth->user_id);
                                pocket()->user->changeReviewStatus($userAuth->user_id);
                                pocket()->userSwitch->postSyncPushTemMsgStateByFollow($userAuth->user_id, true);
                            }
                        }

                        return pocket()->notify->weChatSubscribeNormalMsg();
                    }

                    break;
                case 'unsubscribe':
                    if (isset($parameters['FromUserName']) && $parameters['FromUserName']) {
                        $userAuth = rep()->userAuth->getByWeChatOfficeOpenId($parameters['FromUserName']);
                        if ($userAuth) {
                            pocket()->userFollowOffice->cancelOfficeBind($userAuth->user_id);
                            pocket()->userSwitch->postSyncPushTemMsgStateByFollow($userAuth->user_id, false);
                        }
                    }
                    break;
                case 'SCAN':
                    if (isset($parameters['Ticket']) && $parameters['Ticket']) {
                        $resp = pocket()->userFollowOffice->postBindUserByTicket(
                            $parameters['Ticket'],
                            $parameters['FromUserName']
                        );
                        if ($resp->getStatus()) {
                            pocket()->userSwitch->postSyncPushTemMsgStateByFollow($resp->getData()['user_id'], true);

                            return pocket()->notify->sendWeChatSubscribeMsgByType($resp->getData()['push_msg_type']);
                        } else {
                            return pocket()->notify->weChatSubscribeNormalMsg();
                        }
                    }
                    break;
            }
        }
    }

    /**
     * 元知短信平台回调
     *
     * @param  Request  $request
     *
     * @return ResultReturn
     */
    public function callbackYuanZhiSendMsg(Request $request)
    {
        $params = $request->all();
        foreach ($params as $item) {
            if ($item['status'] == 0) {
                $status = SmsAd::STATUS_SUCCESS;
            } else {
                $status = SmsAd::STATUS_FAIL;
            }
            rep()->smsAd->m()
                ->where('biz_key', $item['uid'])
                ->where('mobile', $item['phone'])
                ->update(['status' => $status, 'send_at' => strtotime($item['report_time'])]);
        }

        die(json_encode(['code' => '00000']));
    }

    /**
     * 智齿客服回调
     *
     * @param  Request  $request
     *
     * @return bool
     */
    public function zhichi(Request $request)
    {
        $callbackData = $request->all();
        $uuid         = $callbackData['partnerId'] ?? 0;
        if (!$uuid) {
            return false;
        }
        $data = [
            'type' => NeteaseCustomCode::KEFU_ZHICHI,
            'data' => ['message' => $callbackData['content'] ?? ""]
        ];
        pocket()->common->sendNimMsgQueueMoreByPocketJob(
            pocket()->netease,
            'msgSendCustomMsg',
            [config('custom.little_helper_uuid'), $uuid, $data]
        );

        return true;
    }

    /**
     * 云信回调
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function neteaseVerify(Request $request)
    {
        $body = $request->all();
        logger()->setLogType('netease_verify')->error(json_encode($body));

        return response()->json([
            'errCode'        => 0,
            'responseCode'   => 20000,
            'modifyResponse' => (object)[],
            'callbackExt'    => '',
        ]);
    }
}

<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Foundation\Services\Guzzle\GuzzleHandle;

/**
 * 更新用户推广渠道数据
 * Class UpdateChannelDataJob
 * @package App\Jobs
 */
class UpdateChannelDataJob extends Job
{
    protected $args;
    protected $appId;
    protected $action;
    protected $userId;
    protected $todayData;
    protected $retryTimes;
    protected $promoteBaseUrl;

    /**
     * UpdateChannelRateJob constructor.
     *
     * @param  string  $action      用户注册或者充值行为
     * @param  int     $userId      实际注册的用户或者实际充值的用户
     * @param  array   $args        相关行为的参数
     * @param  int     $retryTimes  重试次数
     */
    public function __construct($action, $userId, array $args = [], int $retryTimes = 0)
    {
        $this->action         = $action;
        $this->args           = $args;
        $this->userId         = $userId;
        $this->retryTimes     = $retryTimes;
        $this->appId          = config('custom.promote.app_id');//默认小圈，新拉项目，需要修改
        $this->promoteBaseUrl = config('custom.promote.base_url') . '/api/' . $action;

        $this->todayData = Carbon::today()->toDateString();
    }

    public function handle()
    {
        if (!in_array($this->action, ['recharge', 'register'])) {
            logger()->setLogType('2_update_channel_to_ad_' . $this->userId)->error('参数不正确');

            return api_rr()->requestParameterError('参数不正确');
        }
        $userDetail = rep()->userDetail->getByUserId($this->userId);
        if (!$userDetail || !$userDetail->channel) {
            logger()->setLogType('3_update_channel_to_ad_' . $this->userId)->error(json_encode($userDetail));

            return api_rr()->forbidCommon('user_detail不存在');
        }
        try {
            switch ($this->action) {
                case 'register':
                    $this->registerGuzzle($userDetail->channel, $userDetail->os);
                    break;
                case 'recharge':
                    $this->rechargeGuzzle($userDetail->channel, $userDetail->os, $this->args['order_no']);
                    break;
                default:
                    break;
            }
        } catch (\Exception $exception) {
            /**  自动无限次重试 */
            $job = (new UpdateChannelDataJob($this->action, $this->userId, $this->args, $this->retryTimes))
                ->onQueue('update_channel_data')->delay(Carbon::now()->addSeconds(60 * 3));
            dispatch($job);

            logger()->setLogType('4_update_channel_to_ad_' . $this->userId)->error($exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * 上报充值信息
     *
     * @param $channel
     * @param $os
     * @param $orderNo
     *
     * @return bool
     */
    public function rechargeGuzzle($channel, $os, $orderNo)
    {
        $now = time();
        if (!isset($this->args['ori_amount']) || !isset($this->args['amount']) || !isset($this->args['done_at']) || !$orderNo) {
            return false;
        }
        $postArr  = [
            'app_id'     => $this->appId,
            'user_id'    => $this->userId,
            'channel'    => $channel,
            'os'         => $os,
            'order_no'   => $orderNo,
            'ori_amount' => $this->args['ori_amount'] ?? 0,
            'amount'     => $this->args['amount'] ?? 0,
            'done_at'    => $this->args['done_at'] ?? $now,
        ];
        $postArr  = $this->encrypt($postArr);
        $client   = (new GuzzleHandle)->getClient();
        $response = $client->post($this->promoteBaseUrl, [
            'json' => $postArr
        ]);
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        return true;
    }

    /**
     * 上报注册信息
     *
     * @param $channel
     * @param $os
     *
     * @return bool
     */
    public function registerGuzzle($channel, $os)
    {
        if ($channel == 'promote') {
            if ($this->retryTimes <= 5) {
                $this->retryTimes = $this->retryTimes + 1;
                $job              = (new UpdateChannelDataJob('register', $this->userId, [], $this->retryTimes))
                    ->onQueue('update_channel_data')->delay(Carbon::now()->addSeconds(60 * $this->retryTimes));
                dispatch($job);
            }
            logger()->setLogType('5_update_channel_to_ad_' . $this->userId)->error(json_encode($this->retryTimes));

            return true;
        }
        $registerAt = rep()->userDetail->m()->where('user_id', $this->userId)->value('created_at');
        $time       = $registerAt ? $registerAt->timestamp : time();
        $postArr    = [
            'app_id'     => $this->appId,
            'user_id'    => $this->userId,
            'channel'    => $channel,
            'os'         => $os,
            'created_at' => $time,
        ];
        logger()->setLogType('6_update_channel_to_ad_' . $this->userId)->error(json_encode($postArr));
        $postArr = $this->encrypt($postArr);
        $client   = (new GuzzleHandle)->getClient();
        $response = $client->post($this->promoteBaseUrl, [
            'json' => $postArr
        ]);
        if ($response->getStatusCode() !== 200) {
            logger()->setLogType('7_update_channel_to_ad_' . $this->userId)->error($response->getBody()->getContents());

            return false;
        }
        logger()->setLogType('8_update_channel_to_ad_' . $this->userId)->error($response->getBody()->getContents());

        return true;
    }

    /**
     * 对数据加密处理
     *
     * @param  array  $data
     *
     * @return array
     */
    protected function encrypt(array $data) : array
    {
        $encryptToken = sys_encrypt(get_string($data));

        return array_merge($data, ['token' => $encryptToken]);
    }
}

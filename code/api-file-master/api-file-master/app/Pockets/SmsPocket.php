<?php

namespace App\Pockets;

use App\Foundation\Modules\Pocket\BasePocket;
use Carbon\Carbon;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class SmsPocket extends BasePocket
{
    /**
     * 记录手机号错误次数
     */
    public function recordMobileErrorTimes($mobile) : bool
    {
        if ($mobile) {
            $redisKey = config('redis_keys.mobile.error_times.key');
            if (redis()->client()->hGet($redisKey, $mobile) >= 5) {
                redis()->client()->hDel($redisKey, $mobile);
                $blockKey = config('redis_keys.mobile.block.key');
                redis()->client()->set(sprintf($blockKey, $mobile), $mobile);
                redis()->client()->expire(sprintf($blockKey, $mobile), 10 * 60);
            } else {
                redis()->client()->hIncrBy($redisKey, $mobile, 1);
            }
        }

        return true;
    }

    /**
     * 判断手机号是否拉黑
     *
     * @param $mobile
     *
     * @return bool|int
     */
    public function whetherMobileBlock($mobile)
    {
        $blockKey = config('redis_keys.mobile.block.key');

        return redis()->client()->exists(sprintf($blockKey, $mobile));
    }

    /**
     * 登录成功，都需要删除历史累计错误次数
     *
     * @param $mobile
     */
    public function clearMobileErrorTimes($mobile)
    {
        $redisKey = config('redis_keys.mobile.error_times.key');
        redis()->client()->hDel($redisKey, $mobile);

        return true;
    }

    /**
     * 最近一分钟是否发送过短信
     *
     * @param $mobile
     *
     * @return ResultReturn
     */
    public function IsSendSmsLastMinute($mobile)
    {
        if (!app()->environment('production')) {
            return ResultReturn::success("发送成功~");
        }
        $now      = time();
        $client   = redis()->client();
        $redisKey = sprintf(config('redis_keys.sms_block.key'), $mobile);
        $message  = "发送频率过高，请稍后重试~";
        $value    = $client->get($redisKey);
        if ($value) {
            return ResultReturn::failed($message);
        }
        $smsResult = rep()->sms->m()
            ->where('mobile', $mobile)
            ->where('created_at', '>=', Carbon::today()->timestamp)
            ->get();
        if (count($smsResult) >= 5) {
            $endTime = Carbon::tomorrow()->timestamp;
            $client->set($redisKey, $now);
            $client->expire($redisKey, $endTime - $now);

            return ResultReturn::failed($message);
        }

        $lastMinute = $smsResult->where('created_at', '>=', Carbon::createFromTimestamp((time() - 60)))->first();
        if ($lastMinute) {
            return ResultReturn::failed($message);
        }

        return ResultReturn::success("发送成功~");
    }

    /**
     * 限制短信发送成功后，1分钟内不能再次发送
     *
     * @param $mobile
     */
    public function smsLimit($mobile) : bool
    {
        $client   = redis()->client();
        $redisKey = sprintf(config('redis_keys.sms_block.key'), $mobile);
        $client->set($redisKey, time());
        $client->expire($redisKey, 60);

        return true;
    }
}

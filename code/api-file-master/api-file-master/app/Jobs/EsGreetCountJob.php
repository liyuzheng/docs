<?php

namespace App\Jobs;


/**
 * 更新打招呼数量字段es
 *
 * Class UpdateUserActiveToEsJob
 * @package App\Jobs
 */
class EsGreetCountJob extends Job
{
    private $userId;
    private $targetUserId;
    private $timestamp;

    /**
     * EsGreetCountJob constructor.
     *
     * @param $userId
     * @param $targetUserId
     * @param $timestamp
     */
    public function __construct($userId, $targetUserId, $timestamp)
    {
        $this->userId       = $userId;
        $this->targetUserId = $targetUserId;
        $this->timestamp    = $timestamp;
    }

    public function handle()
    {
        $time        = time();
        $lastTwoDays = $time - (config('custom.greet.expire_time')) + 1;
        $count       = rep()->greet->m()
            ->where('target_id', $this->targetUserId)
            ->where('created_at', '>', $lastTwoDays)->count();
        pocket()->esUser->updateUserFieldToEs($this->targetUserId, ['greet_count_two_days' => $count]);
    }
}

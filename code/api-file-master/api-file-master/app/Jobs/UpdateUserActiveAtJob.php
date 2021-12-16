<?php

namespace App\Jobs;

use App\Foundation\Handlers\Tools;
use App\Models\User;

/**
 * 更新用户字段es
 *
 * Class UpdateUserActiveToEsJob
 * @package App\Jobs
 */
class UpdateUserActiveAtJob extends Job
{
    private $userId;
    private $timestamp;
    private $os;
    private $runVersion;
    private $language;

    /**
     * UpdateUserActiveAtJob constructor.
     *
     * @param $userId
     * @param $timestamp
     * @param $os
     * @param $runVersion
     * @param $language
     */
    public function __construct($userId, $timestamp, $os, $runVersion, $language)
    {
        $this->userId     = $userId;
        $this->timestamp  = $timestamp;
        $this->os         = $os;
        $this->runVersion = $runVersion;
        $this->language   = $language;
    }


    public function handle()
    {
        pocket()->user->updateUserActiveAt($this->userId, $this->timestamp, $this->os,
            $this->runVersion, $this->language);

        $user   = rep()->user->getQuery()->select('id', 'uuid', 'gender')->find($this->userId);
        if (optional($user)->gender == User::GENDER_WOMEN) {
            pocket()->common->clodStartSyncDataByPocketJob(pocket()->user, 'updateColdStartUserActiveAt',
                [$user, $this->os, $this->runVersion]);
        }
    }
}

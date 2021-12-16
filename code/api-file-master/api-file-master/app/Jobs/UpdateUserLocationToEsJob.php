<?php

namespace App\Jobs;

use App\Foundation\Handlers\Tools;
use App\Models\User;

/**
 * 更新用户地理位置到es
 *
 * Class UpdateUserActiveToEsJob
 * @package App\Jobs
 */
class UpdateUserLocationToEsJob extends Job
{
    private $userId;
    private $lat;
    private $lng;

    /**
     * GiveMembersJob constructor.
     *
     * @param $userId
     */
    public function __construct($userId, $lng = 0, $lat = 0)
    {
        $this->userId = $userId;
        $this->lat    = $lat;
        $this->lng    = $lng;
    }


    public function handle()
    {
        $result = pocket()->esUser->updateOrPostUserLocation($this->userId, $this->lng, $this->lat);
        $user   = rep()->user->getQuery()->select('id', 'uuid', 'gender')->find($this->userId);
        if (optional($user)->gender == User::GENDER_WOMEN) {
            pocket()->common->clodStartSyncDataByPocketJob(pocket()->user, 'updateColdStartUserLocation',
                [$user, $this->lng, $this->lat]);
        }
    }
}

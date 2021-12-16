<?php

namespace App\Jobs;

use Elasticsearch\ClientBuilder;

/**
 * 更新用户字段es
 *
 * Class UpdateUserActiveToEsJob
 * @package App\Jobs
 */
class UpdateUserFieldToEsJob extends Job
{
    private $userId;
    private $fields;

    /**
     * UpdateUserFieldToEsJob constructor.
     *
     * @param         $userId
     * @param  array  $fileds
     */
    public function __construct($userId, array $fileds = [])
    {
        $this->userId = $userId;
        $this->fields = $fileds;

    }


    public function handle()
    {
        pocket()->esUser->updateUserFieldToEs($this->userId, $this->fields);
    }
}

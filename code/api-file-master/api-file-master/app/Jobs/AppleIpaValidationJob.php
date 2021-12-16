<?php


namespace App\Jobs;

use GuzzleHttp\Exception\GuzzleException;

class AppleIpaValidationJob extends Job
{
    private $memberRecordId;

    /**
     * AppleIpaValidationJob constructor.
     *
     * @param $memberRecordId
     */
    public function __construct($memberRecordId)
    {
        $this->memberRecordId = $memberRecordId;
    }

    /**
     * 请求 apple 确认用户连续包月是否续费
     *
     * @throws GuzzleException
     */
    public function handle()
    {
        $memberRecord = rep()->memberRecord->getQuery()->find($this->memberRecordId);
        $member       = rep()->member->getQuery()->where('user_id', $memberRecord->user_id)->first();
        pocket()->tradePay->processAppleOrdersByMember($memberRecord);

        $updatedMember = rep()->member->getQuery()->where('user_id', $memberRecord->user_id)->first();
        if ($member->duration == $updatedMember->duration) {
            rep()->memberRecord->getQuery()->where('certificate', $memberRecord->certificate)
                ->where('duration', $memberRecord->duration)->update(['next_cycle_at' => 0]);
            $hasContinuousRecords = rep()->memberRecord->getQuery()->where('user_id', $memberRecord->user_id)
                ->where('next_cycle_at', '>', 0)->count();
            if (!$hasContinuousRecords) {
                $member->update(['continuous' => 0]);
            }
        } else {
            $memberRecord->update(['next_cycle_at' => 0]);
        }
    }
}

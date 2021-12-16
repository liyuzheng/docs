<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * 更新用户字段es
 *
 * Class UpdateUserActiveToEsJob
 * @package App\Jobs
 */
class UpdateMemberToEsJob extends Job
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
        $time   = time();
        $member = rep()->member->m()
            ->whereRaw('start_at + duration >= ' . ($time - 5 * 60))
            ->whereRaw('start_at + duration <= ' . ($time + 5 * 60))
            ->where('user_id', $this->userId)
            ->first();
        if ($member) {
            Log::error('ID：' . $this->userId . '取消会员');
            rep()->user->m()->where('id', $this->userId)->update(['hide' => User::SHOW]);
            pocket()->esUser->updateUserFieldToEs($this->userId, ['is_member' => 0, 'hide' => 0]);
        } else {
            Log::error('ID：' . $this->userId . '已续费');
        }
        $job = (new UpdateUserInfoToMongoJob($this->userId))->onQueue('update_user_info_to_mongo');
        dispatch($job);
    }
}

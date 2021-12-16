<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Class JobController
 * @package App\Http\Controllers
 */
class JobController extends BaseController
{
    /**
     * 工作列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function jobs(Request $request)
    {
        $userId = $this->getAuthUserId();
        $gender = rep()->user->getById($userId)->gender;
        $jobs   = rep()->job->m()->select(['uuid', 'name'])->where('gender', $gender)->get();

        return api_rr()->getOK($jobs);
    }

    /**
     * 添加工作
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        $userId = $this->getAuthUserId();
        $uuids  = $request->post('uuids');
        $jobIds = rep()->job->m()->whereIn('uuid', $uuids)->get()->pluck('id')->toArray();
        if (count($jobIds) == 0) {
            return api_rr()->notFoundResult('工作不存在');
        }
        foreach ($jobIds as $jobId) {
            $createData[] = [
                'uuid'       => pocket()->util->getSnowflakeId(),
                'user_id'    => $userId,
                'job_id'     => $jobId,
                'created_at' => time(),
                'updated_at' => time()
            ];
        }
        rep()->userJob->m()->insert($createData);

        return api_rr()->postOK([]);
    }
}

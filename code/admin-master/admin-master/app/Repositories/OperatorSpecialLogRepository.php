<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\OperatorSpecialLog;

class OperatorSpecialLogRepository extends BaseRepository
{
    public function setModel()
    {
        return OperatorSpecialLog::class;
    }

    public function setNewLog($targetUserId, $action, $actionResult, $content, $adminId)
    {
        rep()->operatorSpecialLog->m()->create([
            'target_user_id' => $targetUserId,
            'action'         => $action,
            'action_result'  => $actionResult,
            'content'        => $content,
            'admin_id'       => $adminId,
        ]);
    }
}

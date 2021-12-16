<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\AdminOperationLog;

class AdminOperationLogRepository extends BaseRepository
{
    public function setModel()
    {
        return AdminOperationLog::class;
    }
}

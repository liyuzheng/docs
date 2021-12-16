<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\UserPhotoChangeLog;

class UserPhotoChangeLogRepository extends BaseRepository
{
    public function setModel()
    {
        return UserPhotoChangeLog::class;
    }
}

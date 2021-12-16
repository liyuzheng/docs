<?php


namespace App\Repositories;

use App\Models\UserJob;
use App\Foundation\Modules\Repository\BaseRepository;


class UserJobRepository extends BaseRepository
{
    public function setModel()
    {
        return UserJob::class;
    }
}
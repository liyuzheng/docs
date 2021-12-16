<?php


namespace App\Repositories;

use App\Models\Job;
use App\Foundation\Modules\Repository\BaseRepository;

class JobRepository extends BaseRepository
{
    public function setModel()
    {
        return Job::class;
    }
}
<?php


namespace App\Repositories;


use App\Models\MemberRecord;
use App\Models\Task;
use App\Foundation\Modules\Repository\BaseRepository;

class TaskRepository extends BaseRepository
{
    public function setModel()
    {
        return Task::class;
    }
}

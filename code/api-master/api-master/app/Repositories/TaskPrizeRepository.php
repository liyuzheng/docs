<?php


namespace App\Repositories;


use App\Models\TaskPrize;
use App\Foundation\Modules\Repository\BaseRepository;

class TaskPrizeRepository extends BaseRepository
{
    public function setModel()
    {
        return TaskPrize::class;
    }
}

<?php


namespace App\Repositories;


use App\Models\StatSmsRecall;
use App\Foundation\Modules\Repository\BaseRepository;

class StatSmsRecallRepository extends BaseRepository
{
    public function setModel()
    {
        return StatSmsRecall::class;
    }
}

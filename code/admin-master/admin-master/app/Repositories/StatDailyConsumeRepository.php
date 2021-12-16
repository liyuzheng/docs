<?php


namespace App\Repositories;


use App\Models\StatDailyConsume;
use App\Foundation\Modules\Repository\BaseRepository;

class StatDailyConsumeRepository extends BaseRepository
{
    public function setModel()
    {
        return StatDailyConsume::class;
    }
}

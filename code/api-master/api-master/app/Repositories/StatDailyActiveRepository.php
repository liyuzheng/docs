<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\StatDailyActive;

class StatDailyActiveRepository extends BaseRepository
{
    public function setModel()
    {
        return StatDailyActive::class;
    }
}

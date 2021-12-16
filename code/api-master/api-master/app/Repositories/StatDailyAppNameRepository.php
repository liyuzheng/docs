<?php


namespace App\Repositories;


use App\Models\StatDailyAppName;
use App\Foundation\Modules\Repository\BaseRepository;

class StatDailyAppNameRepository extends BaseRepository
{
    public function setModel()
    {
        return StatDailyAppName::class;
    }
}

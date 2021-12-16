<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\DailyRecord;

class DailyRecordRepository extends BaseRepository
{
    public function setModel()
    {
        return DailyRecord::class;
    }
}

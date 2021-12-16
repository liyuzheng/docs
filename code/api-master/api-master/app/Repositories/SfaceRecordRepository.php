<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\SfaceRecord;

class SfaceRecordRepository extends BaseRepository
{
    public function setModel()
    {
        return SfaceRecord::class;
    }
}

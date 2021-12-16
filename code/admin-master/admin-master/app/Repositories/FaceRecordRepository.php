<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\FaceRecord;

class FaceRecordRepository extends BaseRepository
{
    public function setModel()
    {
        return FaceRecord::class;
    }
}
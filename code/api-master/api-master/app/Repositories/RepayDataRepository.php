<?php


namespace App\Repositories;

use App\Models\RepayData;
use App\Foundation\Modules\Repository\BaseRepository;

class RepayDataRepository extends BaseRepository
{
    public function setModel()
    {
        return RepayData::class;
    }
}

<?php


namespace App\Repositories;

use App\Models\Repay;
use App\Foundation\Modules\Repository\BaseRepository;

class RepayRepository extends BaseRepository
{
    public function setModel()
    {
        return Repay::class;
    }
}

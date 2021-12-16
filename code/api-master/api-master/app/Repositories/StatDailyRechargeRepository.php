<?php


namespace App\Repositories;


use App\Models\StatDailyRecharge;
use App\Foundation\Modules\Repository\BaseRepository;

class StatDailyRechargeRepository extends BaseRepository
{
    public function setModel()
    {
        return StatDailyRecharge::class;
    }
}

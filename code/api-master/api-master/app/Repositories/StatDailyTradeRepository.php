<?php


namespace App\Repositories;


use App\Models\StatDailyTrade;
use App\Foundation\Modules\Repository\BaseRepository;

class StatDailyTradeRepository extends BaseRepository
{
    public function setModel()
    {
        return StatDailyTrade::class;
    }
}

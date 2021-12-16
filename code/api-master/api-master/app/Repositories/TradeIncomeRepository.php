<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\TradeIncome;

class TradeIncomeRepository extends BaseRepository
{
    public function setModel()
    {
        return TradeIncome::class;
    }

}

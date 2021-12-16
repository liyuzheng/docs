<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\TradeBalance;

class TradeBalanceRepository extends BaseRepository
{
    public function setModel()
    {
        return TradeBalance::class;
    }

}

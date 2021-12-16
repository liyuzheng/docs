<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\TradePay;

class TradePayRepository extends BaseRepository
{
    public function setModel()
    {
        return TradePay::class;
    }

}

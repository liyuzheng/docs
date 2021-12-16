<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\TradeWithdraw;

class TradeWithdrawRepository extends BaseRepository
{
    public function setModel()
    {
        return TradeWithdraw::class;
    }

}

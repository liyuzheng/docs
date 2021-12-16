<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Trade;

class TradeRepository extends BaseRepository
{
    public function setModel()
    {
        return Trade::class;
    }

}

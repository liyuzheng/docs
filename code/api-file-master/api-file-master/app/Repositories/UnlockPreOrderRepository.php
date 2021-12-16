<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\UnlockPreOrder;

/**
 * Class UnlockPreOrderRepository
 * @package App\Repositories
 */
class UnlockPreOrderRepository extends BaseRepository
{
    /**
     * @return mixed|string
     */
    public function setModel()
    {
        return UnlockPreOrder::class;
    }
}

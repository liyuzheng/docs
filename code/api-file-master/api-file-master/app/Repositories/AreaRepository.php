<?php


namespace App\Repositories;

use App\Models\Area;
use App\Foundation\Modules\Repository\BaseRepository;

class AreaRepository extends BaseRepository
{
    public function setModel()
    {
        return Area::class;
    }
}

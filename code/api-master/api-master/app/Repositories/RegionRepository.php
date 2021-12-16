<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Region;

class RegionRepository extends BaseRepository
{
    public function setModel()
    {
        return Region::class;
    }
}
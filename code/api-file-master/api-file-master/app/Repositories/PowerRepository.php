<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Power;

class PowerRepository extends BaseRepository
{
    public function setModel()
    {
        return Power::class;
    }
}
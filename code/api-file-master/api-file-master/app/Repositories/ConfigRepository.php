<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Config;

class ConfigRepository extends BaseRepository
{
    public function setModel()
    {
        return Config::class;
    }
}
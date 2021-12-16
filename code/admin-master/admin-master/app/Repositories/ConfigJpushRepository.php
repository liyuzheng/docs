<?php


namespace App\Repositories;

use App\Models\ConfigJpush;
use App\Foundation\Modules\Repository\BaseRepository;

class ConfigJpushRepository extends BaseRepository
{
    public function setModel()
    {
        return ConfigJpush::class;
    }
}

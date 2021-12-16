<?php


namespace App\Repositories;

use App\Models\Resource;
use App\Foundation\Modules\Repository\BaseRepository;


class ResourceRepository extends BaseRepository
{
    public function setModel()
    {
        return Resource::class;
    }
}
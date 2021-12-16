<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\ResourceCheck;

class ResourceCheckRepository extends BaseRepository
{
    public function setModel()
    {
        return ResourceCheck::class;
    }
}

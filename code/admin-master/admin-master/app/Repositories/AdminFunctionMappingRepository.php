<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\AdminFunctionMapping;

class AdminFunctionMappingRepository extends BaseRepository
{
    public function setModel()
    {
        return AdminFunctionMapping::class;
    }
}

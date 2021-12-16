<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\AdminRole;

class AdminRoleRepository extends BaseRepository
{
    public function setModel()
    {
        return AdminRole::class;
    }
}

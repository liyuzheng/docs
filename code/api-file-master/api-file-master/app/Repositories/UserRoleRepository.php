<?php


namespace App\Repositories;

use App\Models\UserRole;
use App\Foundation\Modules\Repository\BaseRepository;

class UserRoleRepository extends BaseRepository
{
    public function setModel()
    {
        return UserRole::class;
    }
}
<?php


namespace App\Repositories;

use App\Models\UserResource;
use App\Foundation\Modules\Repository\BaseRepository;


class UserResourceRepository extends BaseRepository
{
    public function setModel()
    {
        return UserResource::class;
    }
}
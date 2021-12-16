<?php


namespace App\Repositories;


use App\Models\UserDestroy;
use App\Foundation\Modules\Repository\BaseRepository;

class UserDestroyRepository extends BaseRepository
{
    public function setModel()
    {
        return UserDestroy::class;
    }
}

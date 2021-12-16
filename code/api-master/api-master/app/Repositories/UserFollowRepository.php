<?php


namespace App\Repositories;

use App\Models\UserFollow;
use App\Foundation\Modules\Repository\BaseRepository;

class UserFollowRepository extends BaseRepository
{
    public function setModel()
    {
        return UserFollow::class;
    }
}
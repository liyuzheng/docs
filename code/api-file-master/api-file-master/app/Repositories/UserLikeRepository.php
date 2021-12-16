<?php


namespace App\Repositories;

use App\Models\UserLike;
use App\Foundation\Modules\Repository\BaseRepository;

class UserLikeRepository extends BaseRepository
{
    public function setModel()
    {
        return UserLike::class;
    }
}

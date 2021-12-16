<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\UserTag;

class UserTagRepository extends BaseRepository
{
    public function setModel()
    {
        return UserTag::class;
    }
}
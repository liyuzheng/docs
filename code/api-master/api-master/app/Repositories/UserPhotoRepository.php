<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\UserPhoto;

class UserPhotoRepository extends BaseRepository
{
    public function setModel()
    {
        return UserPhoto::class;
    }
}

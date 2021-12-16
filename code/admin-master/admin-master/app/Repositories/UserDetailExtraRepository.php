<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\UserDetailExtra;

class UserDetailExtraRepository extends BaseRepository
{
    public function setModel()
    {
        return UserDetailExtra::class;
    }
}

<?php


namespace App\Repositories;


use App\Models\StatUser;
use App\Foundation\Modules\Repository\BaseRepository;

class StatUserRepository extends BaseRepository
{
    public function setModel()
    {
        return StatUser::class;
    }
}

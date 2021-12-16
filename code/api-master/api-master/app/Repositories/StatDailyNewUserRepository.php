<?php


namespace App\Repositories;


use App\Models\StatDailyNewUser;
use App\Foundation\Modules\Repository\BaseRepository;

class StatDailyNewUserRepository extends BaseRepository
{
    public function setModel()
    {
        return StatDailyNewUser::class;
    }
}

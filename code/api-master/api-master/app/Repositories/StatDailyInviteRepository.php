<?php


namespace App\Repositories;


use App\Models\StatDailyInvite;
use App\Foundation\Modules\Repository\BaseRepository;

class StatDailyInviteRepository extends BaseRepository
{
    public function setModel()
    {
        return StatDailyInvite::class;
    }
}

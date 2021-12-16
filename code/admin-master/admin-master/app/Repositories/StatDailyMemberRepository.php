<?php


namespace App\Repositories;


use App\Models\StatDailyMember;
use App\Foundation\Modules\Repository\BaseRepository;

class StatDailyMemberRepository extends BaseRepository
{
    public function setModel()
    {
        return StatDailyMember::class;
    }
}

<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\MemberPunishment;

class MemberPunishmentRepository extends BaseRepository
{
    public function setModel()
    {
        return MemberPunishment::class;
    }
}

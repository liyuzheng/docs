<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\InviteBuildRecord;

class InviteBuildRecordRepository extends BaseRepository
{
    public function setModel()
    {
        return InviteBuildRecord::class;
    }

}

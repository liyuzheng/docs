<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\UserVisit;

class UserVisitRepository extends BaseRepository
{
    public function setModel()
    {
        return UserVisit::class;
    }
}

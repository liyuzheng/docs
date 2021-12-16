<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\UserEvaluate;

class UserEvaluateRepository extends BaseRepository
{
    public function setModel()
    {
        return UserEvaluate::class;
    }
}
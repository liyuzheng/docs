<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\LoginFaceRecord;

class LoginFaceRecordRepository extends BaseRepository
{
    public function setModel()
    {
        return LoginFaceRecord::class;
    }
}

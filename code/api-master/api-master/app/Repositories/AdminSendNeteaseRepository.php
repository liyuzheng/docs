<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\AdminSendNetease;

class AdminSendNeteaseRepository extends BaseRepository
{
    public function setModel()
    {
        return AdminSendNetease::class;
    }
}

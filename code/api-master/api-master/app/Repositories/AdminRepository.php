<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Admin;

class AdminRepository extends BaseRepository
{
    public function setModel()
    {
        return Admin::class;
    }
}
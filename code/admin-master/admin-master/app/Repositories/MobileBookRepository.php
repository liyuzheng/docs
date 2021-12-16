<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\MobileBook;

class MobileBookRepository extends BaseRepository
{
    public function setModel()
    {
        return MobileBook::class;
    }
}
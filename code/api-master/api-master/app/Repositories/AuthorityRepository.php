<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Authority;

class AuthorityRepository extends BaseRepository
{
    public function setModel()
    {
        return Authority::class;
    }
}

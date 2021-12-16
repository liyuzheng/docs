<?php


namespace App\Repositories;


use App\Models\Greet;
use App\Foundation\Modules\Repository\BaseRepository;

class GreetRepository extends BaseRepository
{
    public function setModel()
    {
        return Greet::class;
    }
}

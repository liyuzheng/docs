<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\SpamWord;

class SpamWordRepository extends BaseRepository
{
    public function setModel()
    {
        return SpamWord::class;
    }
}

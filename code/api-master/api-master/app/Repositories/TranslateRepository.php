<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Translate;

class TranslateRepository extends BaseRepository
{
    public function setModel()
    {
        return Translate::class;
    }
}

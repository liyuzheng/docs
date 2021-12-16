<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Option;

class OptionRepository extends BaseRepository
{
    public function setModel()
    {
        return Option::class;
    }
}

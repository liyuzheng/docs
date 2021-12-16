<?php


namespace App\Repositories;

use App\Models\Moment;
use App\Foundation\Modules\Repository\BaseRepository;

class MomentRepository extends BaseRepository
{
    public function setModel()
    {
        return Moment::class;
    }
}

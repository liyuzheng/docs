<?php


namespace App\Repositories;

use App\Models\Report;
use App\Foundation\Modules\Repository\BaseRepository;

class ReportRepository extends BaseRepository
{
    public function setModel()
    {
        return Report::class;
    }
}
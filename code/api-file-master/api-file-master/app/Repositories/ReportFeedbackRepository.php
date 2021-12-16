<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\ReportFeedback;

class ReportFeedbackRepository extends BaseRepository
{
    public function setModel()
    {
        return ReportFeedback::class;
    }
}

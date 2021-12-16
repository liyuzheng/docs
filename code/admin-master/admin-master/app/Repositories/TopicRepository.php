<?php


namespace App\Repositories;

use App\Models\Topic;
use App\Foundation\Modules\Repository\BaseRepository;

class TopicRepository extends BaseRepository
{
    public function setModel()
    {
        return Topic::class;
    }
}

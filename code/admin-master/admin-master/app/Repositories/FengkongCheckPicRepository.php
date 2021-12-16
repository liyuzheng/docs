<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\FengkongCheckPic;

class FengkongCheckPicRepository extends BaseRepository
{
    public function setModel()
    {
        return FengkongCheckPic::class;
    }
}

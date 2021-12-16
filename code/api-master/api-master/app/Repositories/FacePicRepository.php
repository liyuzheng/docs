<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\FacePic;

class FacePicRepository extends BaseRepository
{
    public function setModel()
    {
        return FacePic::class;
    }
}

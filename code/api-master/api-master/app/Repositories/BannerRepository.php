<?php


namespace App\Repositories;

use App\Models\Banner;
use App\Foundation\Modules\Repository\BaseRepository;

class BannerRepository extends BaseRepository
{
    public function setModel()
    {
        return Banner::class;
    }
}

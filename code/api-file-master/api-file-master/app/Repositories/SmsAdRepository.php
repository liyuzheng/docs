<?php


namespace app\Repositories;


use App\Models\SmsAd;
use App\Foundation\Modules\Repository\BaseRepository;

class SmsAdRepository extends BaseRepository
{
    public function setModel()
    {
        return SmsAd::class;
    }
}

<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Wechat;

class WechatRepository extends BaseRepository
{
    public function setModel()
    {
        return Wechat::class;
    }
}
<?php


namespace App\Repositories;


use App\Models\WechatTemplateMsg;
use App\Foundation\Modules\Repository\BaseRepository;

class WechatTemplateMsgRepository extends BaseRepository
{
    public function setModel()
    {
        return WechatTemplateMsg::class;
    }
}

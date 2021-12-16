<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\SwitchModel;

class SwitchModelRepository extends BaseRepository
{
    public function setModel()
    {
        return SwitchModel::class;
    }

    /**
     * 获得微信模板消息是否推送开关
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getPushTemMsg()
    {
        return rep()->switchModel->m()->where('key', SwitchModel::KEY_PUSH_TEM_MSG)->first();
    }
}

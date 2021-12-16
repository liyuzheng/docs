<?php


namespace App\Repositories;


use App\Models\Sms;
use App\Foundation\Modules\Repository\BaseRepository;

class SmsRepository extends BaseRepository
{
    public function setModel()
    {
        return Sms::class;
    }

    /**
     * 获得最的一条短信记录
     *
     * @param  int    $type    短信类型
     * @param  int    $mobile  手机号码
     * @param  array  $fields  查询字段
     *
     * @return \App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getSmsByType(int $type, int $mobile, array $fields = ['*'])
    {
        return $this->m()->where('mobile', $mobile)
            ->select($fields)
            ->where('type', $type)
            ->where('used_at', 0)
            ->where('expired_at', '>=', time())
            ->orderBy('id', 'desc')
            ->first();
    }
}

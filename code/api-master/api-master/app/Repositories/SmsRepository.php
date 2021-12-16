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
     * @param  int             $type
     * @param  string|int      $certificate
     * @param  string          $authField
     * @param  array|string[]  $fields
     *
     * @return \App\Models\Sms|null
     */
    public function getSmsByTypeAndAuthFiled($type, $certificate, $authField, array $fields = ['*'])
    {
        return $this->m()->where($authField, $certificate)->select($fields)
            ->where('type', $type)->where('used_at', 0)->where('expired_at', '>=', time())
            ->orderBy('id', 'desc')->first();
    }
}

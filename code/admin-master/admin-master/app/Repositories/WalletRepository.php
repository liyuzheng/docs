<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Wallet;

class WalletRepository extends BaseRepository
{
    public function setModel()
    {
        return Wallet::class;
    }

    /**
     * 获取用户钱包
     *
     * @param  int       $userId
     * @param  string[]  $fields
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getByUserId(int $userId, $fields = ['*'])
    {
        return rep()->wallet->m()->select($fields)->where('user_id', $userId)->first();
    }

    /**
     * 获取一批用户钱包
     *
     * @param  array     $userIds
     * @param  string[]  $fields
     *
     * @return array
     */
    public function getByUserIds(array $userIds, $fields = ['*'])
    {
        return rep()->wallet->m()->select($fields)->whereIn('user_id', $userIds)->get();
    }
}

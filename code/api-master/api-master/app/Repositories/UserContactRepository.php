<?php


namespace App\Repositories;

use App\Models\User;
use App\Models\UserContact;
use App\Foundation\Modules\Repository\BaseRepository;


class UserContactRepository extends BaseRepository
{
    public function setModel()
    {
        return UserContact::class;
    }

    /**
     * 通过账户获取用户联系方式
     *
     * @param  User    $user
     * @param  string  $account
     * @param  string  $platform
     *
     * @return mixed
     */
    public function getUserContactByAccountAndPlatform(User $user, string $account, string $platform)
    {
        return $this->getQuery()
            ->where('account', $account)
            ->where('user_id', $user->id)
            ->where('platform', UserContact::PLATFORM_MAPPING[$platform])
            ->first();
    }
}

<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\User;
use App\Models\UserPowder;

class UserPowerRepository extends BaseRepository
{
    /**
     * @return mixed|string
     */
    public function setModel()
    {
        return UserPowder::class;
    }

    /**
     * 获取用户权限
     *
     * @param  User   $user
     * @param  array  $genders
     * @param  array  $members
     * @param  array  $roles
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserPowers(User $user, $genders, $members, $roles)
    {
        $queryCallback = function ($query) use ($genders, $members, $user, $roles) {
            $query->where(function ($query) use ($genders, $members, $user, $roles) {
                $query->whereIn('gender', $genders)->whereIn('member', $members)
                    ->whereIn('role', $roles);
            })->orWhere('type', UserPowder::TYPE_BOOLEAN);
        };

        $query  = rep()->userPower->getQuery()->where($queryCallback);
        $powers = (clone $query)->where('appname', user_agent()->appName)->get();
        if ($powers->count()) {
            $uniquePowerKeys = $powers->pluck('key')->toArray();
            $query           = $query->where(function ($query) use ($uniquePowerKeys) {
                $query->where('appname', UserPowder::COMMON_APP_NAME)
                    ->whereNotIn('key', $uniquePowerKeys);
            });
        } else {
            $query = $query->where('appname', UserPowder::COMMON_APP_NAME);
        }

        $commonPowers = $query->get();

        return $powers->concat($commonPowers);
    }
}

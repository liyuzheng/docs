<?php


namespace App\Repositories;


use App\Models\StatRemainLoginLog;
use App\Foundation\Modules\Repository\BaseRepository;

class StatRemainLoginLogRepository extends BaseRepository
{
    public function setModel()
    {
        return StatRemainLoginLog::class;
    }

    /**
     * 获得某个时间时间注册的用户在某个时间登陆的人数
     *
     * @param $regSt
     * @param $regEt
     * @param $loginSt
     * @param $loginEt
     *
     * @return int
     */
    public function getUserRemainCountByTime($regSt, $regEt, $loginSt, $loginEt)
    {
        return rep()->statRemainLoginLog->m()
            ->whereBetween('register_at', [$regSt, $regEt])
            ->whereBetween('login_at', [$loginSt, $loginEt])
            ->count();
    }
}

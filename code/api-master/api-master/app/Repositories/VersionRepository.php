<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Version;

class VersionRepository extends BaseRepository
{
    public function setModel()
    {
        return Version::class;
    }

    /**
     * 根据系统和版本号获取版本记录
     *
     * @param int    $os
     * @param string $version
     * @param string $appName
     *
     * @return \App\Models\Version|null
     */
    public function getRecordByOsAndVersionAndAppName(int $os, string $version, string $appName)
    {
        return $this->getQuery()->where('appname', $appName)->where('os', $os)
            ->where('version', $version)->first();
    }
}

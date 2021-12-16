<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\Version;

class VersionPocket extends BasePocket
{
    /**
     * app是否在审核中
     *
     * @param  string  $appName
     * @param  string  $version
     * @param  string  $channel
     *
     * @return bool
     */
    public function whetherAndroidAuditing(string $appName, string $version, string $channel)
    {
        $version = rep()->version->m()
            ->where('appname', $appName)
            ->where('version', $version)
            ->where('channel', $channel)
            ->first();
        if (!$version) {
            return false;
        }
        if ($version->os == Version::OS_IOS) {
            return false;
        }
        if ($version->audited_at) {
            return false;
        }

        return true;
    }
}

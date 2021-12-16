<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Config;
use App\Models\ConfigJpush;

class ConfigPocket extends BasePocket
{
    /**
     * 获得最小ios下载地址
     *
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed|string
     */
    public function getLatestIosUrl()
    {
        $config = rep()->config->m()->select('value')->where('key', Config::KEY_APPLE_LATEST_URL)->first();

        return $config ? $config->value : '';
    }

    /**
     * 获得最小ios下载地址
     *
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed|string
     */
    public function getLatestAndroidUrl()
    {
        $config = rep()->config->m()->select('value')->where('key', Config::KEY_ANDROID_LATEST_URL)->first();

        return $config ? $config->value : '';
    }

    /**
     * 获得极光登录配置
     *
     * @param $appName
     *
     * @return ResultReturn
     */
    public function getJPushConfigByAppName($appName)
    {
        $config = rep()->configJpush->m()->where('appname', $appName)
            ->where('status', ConfigJpush::STATUS_SUCCEED)
            ->orderBy('id', 'desc')
            ->first();
        if (!$config) {
            return ResultReturn::failed(trans('messages.not_found_config'));
        }

        return ResultReturn::success([
            'key'         => $config->key,
            'secret'      => $config->secret,
            'private_key' => $config->private_key,
        ]);
    }

    /**
     * 获得邀请永久下载地址
     *
     * @param  int  $inviteCode
     *
     * @return string
     */
    public function getForeverInviteUrl(int $inviteCode)
    {
        $config = rep()->config->m()->where('key', 'invite_forever_domain')->first();

        return $config ? $config->value . '/' . $inviteCode : '';
    }

    /**
     * 获得邀请master url
     *
     * @param  int  $inviteCode
     *
     * @return string
     */
    public function getMasterInviteUrl(int $inviteCode)
    {
        $config = rep()->config->m()->where('key', 'invite_master_domain')->first();

        return $config ? $config->value . '/s?id=' . $inviteCode : '';
    }

    /**
     * 获得邀请的slave域名
     *
     * @return array|false|string[]
     */
    public function getSlaveInviteDomain()
    {
        $config = rep()->config->m()->where('key', 'invite_slave_domain')->first();

        return $config ? explode(',', $config->value) : [];
    }

    /**
     * 获得一致允许注册的用户设备号
     *
     * @return array|false|string[]
     */
    public function getOpenRegisterClientIds()
    {
        $config = rep()->config->m()->where('key', 'open_register_client_ids')->first();

        return $config ? explode(',', $config->value) : [];
    }

    /**
     * 要变成渠道的appname, 胡梦龙要看某个包的数据
     *
     * @return array|false|string[]
     */
    public function getBecomeChannelAppName()
    {
        $config = rep()->config->m()->where('key', 'become_channel_appname')->first();

        return $config ? explode(',', $config->value) : [];
    }
}

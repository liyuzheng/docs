<?php
/**
 * Created by PhpStorm.
 * User: ailuoy
 * Date: 2019/2/19
 * Time: 下午11:51
 */

namespace App\Foundation\Modules\UserAgent;

use Illuminate\Support\Arr;

/**
 * @property UserAgent $os
 * @property UserAgent $osVersion
 * @property UserAgent $device
 * @property UserAgent $clientType
 * @property UserAgent $appName
 * @property UserAgent $clientVersion
 * @property UserAgent $userAgent
 *
 * Class UserAgent
 * @package App\Foundation\Services\UserAgent
 */
class UserAgent
{
    protected static $has = false;
    private const ANDROID_INVALID_VERSIONS = [1 => '1.0.0', 2 => '1.2.0', 3 => '1.3.0'];

    public function __get($property)
    {
        if (!self::$has) {
            user_agent()->setUserAgentToContext();
            self::$has = true;
        }
        if (isset($this->$property)) {
            return $this->$property;
        }

        if (method_exists($this, $property)) {
            return $this->$property();
        }

        return '';
    }

    /**
     * Set ua to the context
     */
    public function setUserAgentToContext()
    {
        $ua       = separation_user_agent($this->defaultUserAgent(), $this->userAgent());
        $clientOs = strtolower(Arr::get($ua, 'os'));
        $version  = Arr::get($ua, 'client_version');
        if ($clientOs == 'android' && strlen($version) == 1) {
            $version = self::ANDROID_INVALID_VERSIONS[$version] ?? $version;
        }

        context()->set('user_agent_os', $clientOs);
        context()->set('user_agent_os_version', Arr::get($ua, 'os_version'));
        context()->set('user_agent_device', Arr::get($ua, 'device'));
        context()->set('user_agent_client_type', Arr::get($ua, 'client_type'));
        context()->set('user_agent_client_name', Arr::get($ua, 'client_name'));
        context()->set('user_agent_client_version', $version);
    }

    /**
     * Get the useragent
     *
     * @return null|string|string[]
     */
    private function userAgent()
    {
        return request()->headers->has('user-agent') ? request()->headers->get('user-agent') : '';
    }

    /**
     * Get the useragent
     *
     * @return null|string|string[]
     */
    private function defaultUserAgent()
    {
        return request()->headers->has('Ua-Custom') ? request()->headers->get('Ua-Custom') : '';
    }

    /**
     * Get the operating system
     *
     * @return mixed
     */
    private function os()
    {
        return context()->get('user_agent_os');
    }

    /**
     * Get the system version number
     *
     * @return mixed
     */
    private function osVersion()
    {
        return context()->get('user_agent_os_version');
    }

    /**
     * Get the client driver
     *
     * @return mixed
     */
    private function device()
    {
        return context()->get('user_agent_device');
    }

    /**
     * Get the clientType
     *
     * @return mixed
     */
    private function clientType()
    {
        return context()->get('user_agent_client_type');
    }

    /**
     * Get the appName
     *
     * @return mixed
     */
    private function appName()
    {
        return context()->get('user_agent_client_name');
    }

    /**
     * Get the clientVersion
     *
     * @return mixed
     */
    private function clientVersion()
    {
        return context()->get('user_agent_client_version');
    }
}

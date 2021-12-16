<?php
/**
 * Created by PhpStorm.
 * User: ailuoy
 * Date: 2019/3/5
 * Time: 下午2:57
 */

namespace App\Foundation\Services\Guzzle;


use GuzzleHttp\Client;

class GuzzleHandle
{
    /**
     * @param  array  $config
     *
     * @return Client
     */
    public function getClient($config = [])
    {
        $defaultConfig = ['timeout' => 5];

        return new Client(array_merge($config, $defaultConfig));
    }
}

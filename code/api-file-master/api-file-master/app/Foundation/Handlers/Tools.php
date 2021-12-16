<?php

namespace App\Foundation\Handlers;

use App\Foundation\Modules\ResultReturn\ResultReturn;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

/**
 * Trait UtilsHandler
 * @package App\Foundation\Traits
 */
class Tools
{

    /**
     * Determine whether the client has been instantiated during the lifecycle
     * Returns if instantiated， Otherwise, create save and return
     *
     * @return \GuzzleHttp\Client
     */
    public static function getHttpRequestClient()
    {
        if (context()->has('http_request_client')) {
            return context('http_request_client');
        }

        $client = new Client();

        return context()->set('http_request_client', $client);
    }

    /**
     * Get pictures to local through the Internet
     *
     * @param  string  $image_address
     * @param  string  $save_path
     * @param  string  $file_name
     *
     * @return \App\Foundation\Modules\ResultReturn\ResultReturn
     */
    public static function getImageByNetwork(string $image_address, string $save_path, $file_name = '')
    {
        !is_dir($save_path) && mkdir($save_path, 0777, true);
        $return_file_name = '';

        try {
            $response = static::getHttpRequestClient()->request('GET', $image_address);

            if ($response->getStatusCode() == 200) {
                $file_name = $file_name ?: get_md5_random_str() . '.png';
                $contents  = $response->getBody()->getContents();

                if (!Storage::disk('uploads')->put($save_path . $file_name, $contents)) {
                    $message = sprintf('save file to path: %s failure', $save_path . $file_name);

                    return ResultReturn::failed($message);
                }

                $return_file_name = $file_name;
            }
        } catch (Exception $exception) {
            $message = sprintf('Request failure error: %s', $exception->getMessage());

            return ResultReturn::failed($message);
        }

        return ResultReturn::success($return_file_name);
    }


    /**
     * Determine url validity
     *
     * @param  string  $url
     *
     * @return \App\Foundation\Modules\ResultReturn\ResultReturn
     */
    public static function isValidUrl(string $url)
    {
        try {
            $response = get_headers($url, 1);
        } catch (Exception $exception) {
            $message = sprintf('Request url failure address: %s, error: %s', $url, $exception->getMessage());

            return ResultReturn::failed($message);
        }

        if (preg_match('/200/', $response[0])) {
            return utils()->resultReturn()->success($response);
        }

        return ResultReturn::failed('Response status code not 200');
    }
}

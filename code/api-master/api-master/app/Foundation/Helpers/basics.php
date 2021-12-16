<?php
/**
 * Created by PhpStorm.
 * User: ailuoy
 * Date: 2019/2/21
 * Time: 上午9:56
 */

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Foundation\Services\Guzzle\GuzzleHandle;
use App\Foundation\Services\Utils\UtilServices;

if (!function_exists('array_get')) {
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string              $key
     * @param  mixed               $default
     *
     * @return mixed
     *
     * @deprecated Arr::get() should be used directly instead. Will be removed in Laravel 6.0.
     */
    function array_get($array, $key, $default = null)
    {
        return Arr::get($array, $key, $default);
    }
}

if (!function_exists('array_first')) {
    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param  array          $array
     * @param  callable|null  $callback
     * @param  mixed          $default
     *
     * @return mixed
     *
     * @deprecated Arr::first() should be used directly instead. Will be removed in Laravel 6.0.
     */
    function array_first($array, callable $callback = null, $default = null)
    {
        return Arr::first($array, $callback, $default);
    }
}

if (!function_exists('array_random')) {
    /**
     * Get a random value from an array.
     *
     * @param  array     $array
     * @param  int|null  $num
     *
     * @return mixed
     *
     * @deprecated Arr::random() should be used directly instead. Will be removed in Laravel 6.0.
     */
    function array_random($array, $num = null)
    {
        return Arr::random($array, $num);
    }
}

if (!function_exists('snake_case')) {
    /**
     * Convert a string to snake case.
     *
     * @param  string  $value
     * @param  string  $delimiter
     *
     * @return string
     *
     * @deprecated Str::snake() should be used directly instead. Will be removed in Laravel 6.0.
     */
    function snake_case($value, $delimiter = '_')
    {
        return Str::snake($value, $delimiter);
    }
}


if (!function_exists('rep')) {
    /**
     * @return App\Foundation\Modules\Repository\RepositoriesHandle
     */
    function rep()
    {
        return app('rep');
    }
}

if (!function_exists('rr')) {
    /**
     *
     * @return App\Foundation\Modules\ResultReturn\ResultReturn
     */
    function rr()
    {
        return app('rr');
    }
}

if (!function_exists('api_rr')) {
    /**
     *
     * @return App\Foundation\Modules\Response\ApiBusinessResponseHandle
     */
    function api_rr()
    {
        return app('api_rr');
    }
}

if (!function_exists('d')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed  $args
     *
     * @return void
     */
    function d(...$args)
    {
        foreach ($args as $argument) {
            dump($argument);
        }
    }
}

if (!function_exists('get_sql')) {
    /**
     * To monitor and print execute SQL statements
     *
     * @param  bool  $die
     *
     * @return void
     */
    function get_sql($die = false)
    {
        \Illuminate\Support\Facades\DB::listen(function ($sql) use ($die) {
            $singleSql = $sql->sql;
            if ($sql->bindings) {
                foreach ($sql->bindings as $replace) {
                    $value     = is_numeric($replace) ? $replace : "'" . $replace . "'";
                    $singleSql = preg_replace('/\?/', $value, $singleSql, 1);
                }
            }
            if ($die) {
                dd($singleSql);
            } else {
                d($singleSql);
            }
        });
    }
}

if (!function_exists('context')) {
    /**
     * Gets data that is saved only once in the life cycle
     *
     * @param  null|string|array  $key
     * @param  null|mixed         $default
     *
     * @return \App\Foundation\Modules\Context\ContextHandler|mixed|array
     */
    function context($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('context');
        }

        if (is_array($key)) {
            return app('context')->only($key);
        }

        $value = app('context')->$key;

        return is_null($value) ? value($default) : $value;
    }
}

if (!function_exists('request')) {
    /**
     * Get an instance of the current request or an input item from the request.
     *
     * @param  array|string  $key
     * @param  mixed         $default
     *
     * @return \Illuminate\Http\Request|string|array
     */
    function request($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('request');
        }

        if (is_array($key)) {
            return app('request')->only($key);
        }

        $value = app('request')->__get($key);

        return is_null($value) ? value($default) : $value;
    }
}

if (!function_exists('logger')) {
    /**
     * Return log object
     *
     * @return \App\Foundation\Modules\Logger\LoggerHandler
     */
    function logger()
    {
        return new \App\Foundation\Modules\Logger\LoggerHandler();
    }
}

if (!function_exists('user_agent')) {
    /**
     * @return \App\Foundation\Modules\UserAgent\UserAgent
     */
    function user_agent()
    {
        return new App\Foundation\Modules\UserAgent\UserAgent();
    }
}


if (!function_exists('redis')) {
    /**
     * redis helpers
     *
     * @param  string  $connection
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    function redis($connection = 'default')
    {
        return \Illuminate\Support\Facades\Redis::connection($connection);
    }
}


if (!function_exists('mongodb')) {
    /**
     * @param $table
     *
     * @return \Jenssegers\Mongodb\Eloquent\Builder
     */
    function mongodb($table)
    {
        return \App\Foundation\Services\Mongodb\Mongodb::connectionMongodb($table);
    }
}

if (!function_exists('separation_user_agent')) {

    /**
     * Separation user_agent
     * app/1.2.3 iOS/11.2.6 (iPhone 7 Plus)
     *
     * @param $userAgent
     * @param $default
     *
     * @return array|string[]
     */
    function separation_user_agent($userAgent, $default)
    {
        $match      = '/^(.*)\/(.*) (.*)\/(.*) \((.*)\)$/';
        $hasMatches = preg_match($match, $userAgent, $matches)
            || preg_match($match, $default, $matches);
        if ($hasMatches) {
            return [
                'os'             => $matches[3],
                'os_version'     => $matches[4],
                'device'         => $matches[5],
                'client_type'    => 'mobile app',
                'client_name'    => $matches[1],
                'client_version' => $matches[2],
            ];
        } else {
            return [
                'os'             => '',
                'os_version'     => '',
                'device'         => '',
                'client_type'    => 'mobile app',
                'client_name'    => '',
                'client_version' => '',
            ];
        }
    }
}

if (!function_exists('db_slaves')) {
    /**
     * Get the read-write separation configuration
     *
     * @param $env
     *
     * @return array
     */
    function db_slaves($env)
    {
        $slaves   = env($env);
        $slaves   = explode(';', $slaves);
        $dbSlaves = [];
        foreach ($slaves as $key => $slave) {
            foreach (explode(',', $slave) as $item) {
                $temp                     = explode(':', $item);
                $dbSlaves[$key][$temp[0]] = $temp[1];
            }
        }

        return $dbSlaves;
    }
}

if (!function_exists('pocket')) {
    /**
     * get pocket
     *
     * @return App\Foundation\Modules\Pocket\PocketHandle
     */
    function pocket()
    {
        return app('pocket');
    }
}

if (!function_exists('disk_path')) {
    /**
     * @param          $path
     * @param  string  $diskNo
     *
     * @return array
     * @throws Exception
     */
    function disk_path($path, $diskNo = 'disk_0')
    {
        $diskPaths = [
            'disk_0' => 'uploads/'
        ];
        if (!isset($diskPaths[$diskNo])) {
            throw new Exception($diskNo . '磁盘设置不存在,请在settings.disk_path_prefix中设置');
        }
        $diskPrefix = $diskPaths[$diskNo];

        return [
            'db_path' => $diskPrefix . $path,
        ];
    }
}

if (!function_exists('cdn_url')) {
    /**
     * @param $url
     *
     * @return string
     */
    function cdn_url($url)
    {
        return config('custom.cdn_url') . $url;
    }
}

if (!function_exists('cdn_http_url')) {
    /**
     * @param $url
     *
     * @return string
     */
    function cdn_http_url($url)
    {
        return config('custom.cdn_http_url') . $url;
    }
}

if (!function_exists('file_url')) {
    /**
     * @param $url
     *
     * @return string
     */
    function file_url($url)
    {
        return config('custom.file_url') . $url;
    }
}

if (!function_exists('req_get_query')) {
    /**
     * @return \App\Foundation\Modules\Context\ContextHandler|array|mixed
     */
    function req_get_query()
    {
        return context('req_get_query');
    }
}

if (!function_exists('req_json_payload')) {
    /**
     * @return \App\Foundation\Modules\Context\ContextHandler|array|mixed
     */
    function req_json_payload()
    {
        return context('req_json_payload');
    }
}

if (!function_exists('req_headers')) {
    /**
     * @return \App\Foundation\Modules\Context\ContextHandler|array|mixed
     */
    function req_headers()
    {
        return context('req_headers');
    }
}

if (!function_exists('array_last')) {
    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param  array          $array
     * @param  callable|null  $callback
     * @param  mixed          $default
     *
     * @return mixed
     *
     * @deprecated Arr::last() should be used directly instead. Will be removed in Laravel 6.0.
     */
    function array_last($array, callable $callback = null, $default = null)
    {
        return Arr::last($array, $callback, $default);
    }
}

if (!function_exists('str_random')) {
    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param  int  $length
     *
     * @return string
     *
     * @throws \RuntimeException
     *
     * @deprecated Str::random() should be used directly instead. Will be removed in Laravel 6.0.
     */
    function str_random($length = 16)
    {
        return Str::random($length);
    }
}

if (!function_exists('dda')) {
    /**
     * 对一个模型数组化
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    function dda($model)
    {
        dd($model->toArray());
    }
}

if (!function_exists('is_valid_url')) {
    /**
     * 检验url是否正确
     *
     * @param $url
     *
     * @return bool
     */
    function is_valid_url($url)
    {
        $array = get_headers($url, 1);

        return preg_match('/200/', $array[0]) ? true : false;
    }
}

if (!function_exists('web_url')) {
    /**
     * get uri
     *
     * @param $uri
     *
     * @return string
     */
    function web_url($uri)
    {
        return config('custom.web_url') . $uri;
    }
}


if (!function_exists('get_share_uri')) {
    /**
     * get uri
     *
     * @param $uri
     *
     * @return string
     */
    function get_share_uri($uri)
    {
        return config('custom.share_domain') . $uri;
    }
}

if (!function_exists('get_random_float')) {
    /**
     * Gets a random number between 0 and 1
     *
     * @param  int  $min
     * @param  int  $max
     *
     * @return float|int
     * @throws Exception
     */
    function get_random_float($min = 0, $max = 1)
    {
        return $min + random_int(0, PHP_INT_MAX) / PHP_INT_MAX * ($max - $min);
    }
}
if (!function_exists('object_array')) {
    /**
     * 对象处理
     *
     * @return mixed|array
     */
    function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = object_array($value);
            }
        }

        return $array;
    }
}

if (!function_exists('get_string')) {
    /**
     * get_string
     *
     * @return mixed|array
     */
    function get_string($response)
    {

        $str = "";

        foreach ($response as $key => $value) {
            $str .= $key . "=" . $value . "&";
        }
        $getSign = substr($str, 0, strlen($str) - 1);

        return $getSign;
    }
}

if (!function_exists('generate_order_no')) {
    /**
     * getString
     *
     * @return mixed|array
     */
    function generate_order_no()
    {
        return date('YmdHis', time()) . rand(1, 10000);
    }
}

if (!function_exists('get_api_uri')) {
    /**
     * get uri
     *
     * @param $uri
     *
     * @return string
     */
    function get_api_uri($uri)
    {
        return config('custom.api_domain') . $uri;
    }
}
if (!function_exists('arr_to_obj')) {
    /**
     * get uri
     *
     * @param $uri
     *
     * @return string
     */
    function arr_to_obj($array)
    {
        $paymentObj = new \stdClass();
        foreach ($array as $k => $v) {
            $paymentObj->$k = $v;
        }

        return $paymentObj;
    }
}

if (!function_exists('get_obj_properties')) {
    /**
     * 获取私有属性
     *
     * @param        $obj
     * @param  bool  $removeNull  干掉空值
     *
     * @return array
     */
    function get_obj_properties($obj, $removeNull = true)
    {
        $reflect = new \ReflectionClass($obj);
        $props   = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED);

        $array = [];
        foreach ($props as $prop) {
            $prop->setAccessible(true);
            $key   = $prop->getName();
            $value = $prop->getValue($obj);

            if ($removeNull == true && $value === null) {
                continue;
            }

            if (is_object($value)) {
                $value = get_obj_properties($value);
            }

            $array[$key] = $value;
        }

        return $array;
    }
}
if (!function_exists('array_to_object')) {
    /**
     * 数组 转 对象
     *
     * @param  array  $arr  数组
     *
     * @return object
     */
    function array_to_object($arr)
    {
        if (gettype($arr) != 'array') {
            return;
        }
        foreach ($arr as $k => $v) {
            if (gettype($v) == 'array' || getType($v) == 'object') {
                $arr[$k] = (object)array_to_object($v);
            }
        }

        return (object)$arr;
    }
}

if (!function_exists('filter_text')) {
    /**
     * 过滤非法的字符串
     *
     * @param $string
     *
     * @return string
     */
    function filter_text($string)
    {
        $arr = ["/", "\\", "%", "?", "？"];
        foreach ($arr as $item) {
            $string = str_replace($item, "", $string);
        }

        return stripslashes(htmlspecialchars($string));
    }
}

if (!function_exists('update_batch')) {
    /**
     * 批量更新某张表的数据
     *
     * @param         $tableName
     * @param  array  $multipleData
     *
     * @return bool|int
     */
    function update_batch($tableName, $multipleData = [])
    {
        try {
            if (empty($multipleData)) {
                throw new \Exception("数据不能为空");
            }
            $firstRow     = current($multipleData);
            $updateColumn = array_keys($firstRow);
            // 默认以id为条件更新，如果没有ID则以第一个字段为条件
            $referenceColumn = isset($firstRow['id']) ? 'id' : current($updateColumn);
            unset($updateColumn[0]);
            // 拼接sql语句
            $updateSql = "UPDATE " . $tableName . " SET ";
            $sets      = [];
            $bindings  = [];
            foreach ($updateColumn as $uColumn) {
                $setSql = "`" . $uColumn . "` = CASE ";
                foreach ($multipleData as $data) {
                    $setSql     .= "WHEN `" . $referenceColumn . "` = ? THEN ? ";
                    $bindings[] = $data[$referenceColumn];
                    $bindings[] = $data[$uColumn];
                }
                $setSql .= "ELSE `" . $uColumn . "` END ";
                $sets[] = $setSql;
            }
            $updateSql .= implode(', ', $sets);
            $whereIn   = collect($multipleData)->pluck($referenceColumn)->values()->all();
            $bindings  = array_merge($bindings, $whereIn);
            $whereIn   = rtrim(str_repeat('?,', count($whereIn)), ',');
            $updateSql = rtrim($updateSql, ", ") . " WHERE `" . $referenceColumn . "` IN (" . $whereIn . ")";

            // 传入预处理sql语句和对应绑定数据
            return DB::update($updateSql, $bindings);
        } catch (\Exception $e) {

            return false;
        }
    }
}

if (!function_exists('is_json')) {
    /**
     * 判断是否是json
     *
     * @param $string
     *
     * @return bool
     */
    function is_json($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }
}

if (!function_exists('is_multi_array')) {
    /**
     * 是否多维数组
     *
     * @param $array
     *
     * @return bool
     */
    function is_multi_array($array)
    {

        if (count($array) == count($array, 1)) {
            return false;
        } else {
            return true;
        }
    }
}

if (!function_exists('db')) {
    /**
     * 选择不同的数据库链接
     *
     * @param  string  $databaseName
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    function db($databaseName = 'mysql')
    {
        return DB::connection($databaseName);
    }
}

if (!function_exists('get_client_real_ip')) {
    /**
     * 获取客户端的真实IP
     *
     * @return mixed|string
     */
    function get_client_real_ip()
    {
        $ipKey  = ['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        $server = request()->server;
        if ($server->has('HTTP_X_FORWARDED_FOR')) {
            $forwardedFor = explode(',', $server->get('HTTP_X_FORWARDED_FOR'));
            if (!empty($forwardedFor)) {
                return trim($forwardedFor[0]);
            }
        }

        foreach ($ipKey as $item) {
            if ($server->has($item)) {
                return $server->get($item);
            }
        }

        return '';
    }
}

if (!function_exists('bcrypt')) {
    /**
     * Hash the given value against the bcrypt algorithm.
     *
     * @param  string  $value
     * @param  array   $options
     *
     * @return string
     */
    function bcrypt($value, $options = [])
    {
        return app('hash')->driver('bcrypt')->make($value, $options);
    }
}

if (!function_exists('sys_encrypt')) {
    /**
     * 自定义参数加密方法
     *
     * @param  string  $data    要加密的字符串
     * @param  string  $key     加密密钥
     * @param  int     $expire  过期时间 单位 秒
     */
    function sys_encrypt($data, $key = '', $expire = 0)
    {
        $key  = md5(empty($key) ? config('custom.api.halt') : $key);
        $data = base64_encode($data);
        $x    = 0;
        $len  = strlen($data);
        $l    = strlen($key);
        $char = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }
        $str = sprintf('%010d', $expire ? $expire + time() : 0);
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
        }

        return mb_substr(str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str)), -20);
    }
}
if (!function_exists('sys_decrypt')) {
    /**
     * 系统解密方法
     *
     * @param  string  $data  要解密的字符串 （必须是think_encrypt方法加密的字符串）
     * @param  string  $key   加密密钥
     */
    function sys_decrypt($data, $key = '')
    {
        $key  = md5(empty($key) ? config('custom.api.halt') : $key);
        $data = str_replace(array('-', '_'), array('+', '/'), $data);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        $data   = base64_decode($data);
        $expire = substr($data, 0, 10);
        $data   = substr($data, 10);
        if ($expire > 0 && $expire < time()) {
            return '';
        }
        $x    = 0;
        $len  = strlen($data);
        $l    = strlen($key);
        $char = $str = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            } else {
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }

        return base64_decode($str);
    }
}


if (!function_exists('pd')) {
    /**
     * 跳过dump_server的打印,可接受多个参数
     *
     * @param  mixed  ...$data
     */
    function pd(...$data)
    {
        foreach ($data as $item) {
            p_one($item);
        }
        die();
    }
}
if (!function_exists('p_one')) {
    /**
     * 跳过dump_server的打印
     *
     * @return mixed|string
     */
    function p_one($data)
    {
        // 定义样式
        $str = '<pre style="display: block;padding: 9.5px;margin: 44px 0 0 0;font-size: 13px;line-height: 1.42857;color: #333;word-break: break-all;word-wrap: break-word;background-color: #F5F5F5;border: 1px solid #CCC;border-radius: 4px;">';
        // 如果是boolean或者null直接显示文字；否则print
        if (is_bool($data)) {
            $showData = $data ? 'true' : 'false';
        } elseif (is_null($data)) {
            $showData = 'null';
        } else {
            $showData = print_r($data, true);
        }
        $str .= $showData;
        $str .= '</pre>';
        echo $str;
    }
}

if (!function_exists('birthday_to_age')) {
    /**
     * 生日转年龄
     *
     * @param $birthday
     *
     * @return false|int|string
     */
    function birthday_to_age($birthday)
    {
        $age      = 0;
        $year     = date('Y', $birthday);
        $month    = date('m', $birthday);
        $day      = date('d', $birthday);
        $nowYear  = date('Y');
        $nowMonth = date('m');
        $nowDay   = date('d');

        if ($nowYear > $year) {
            $age = $nowYear - $year - 1;
            if ($nowMonth > $month) {
                $age++;
            } elseif ($nowMonth == $month) {
                if ($nowDay >= $day) {
                    $age++;
                }
            }
        }
        if (empty($birthday)) {
            $age = 0;
        }

        return $age;
    }
}


if (!function_exists('storage')) {
    /**
     * storage
     *
     * @param  string  $disk
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter
     */
    function storage($disk = 'base')
    {
        return \Illuminate\Support\Facades\Storage::disk($disk);
    }
}

if (!function_exists('parse_mine_type_to_ext')) {
    /**
     * 解析mine type转化成后缀
     *
     * @param  string  $mineType
     *
     * @return string
     */
    function parse_mine_type_to_ext($mineType = '/')
    {
        [$prefix, $ext] = explode('/', $mineType);

        return $ext;
    }
}


if (!function_exists('get_distance')) {
    /** 计算两个经纬度坐标的距离
     *
     * @param        $lat1
     * @param        $lng1
     * @param        $lat2
     * @param        $lng2
     * @param  bool  $miles  是否是公里
     *
     * @return float|int
     */
    function get_distance($lng1, $lat1, $lng2, $lat2, $miles = true)
    {
        $pi80  = M_PI / 180;
        $lat1  *= $pi80;
        $lng1  *= $pi80;
        $lat2  *= $pi80;
        $lng2  *= $pi80;
        $r     = 6372.797; //地球的半径 km
        $dlat  = $lat2 - $lat1;
        $dlng  = $lng2 - $lng1;
        $a     = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
        $c     = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $km    = $r * $c;
        $miles = $miles ? ($km * 0.621371192 * 1.609344) : ($km * 0.621371192 * 1.609344 * 1000);

        return (float)sprintf("%.3f", $miles);
    }
}

if (!function_exists('get_distance_str')) {
    /** 计算两个经纬度坐标的距离带单位
     *
     * @param        $lat1
     * @param        $lng1
     * @param        $lat2
     * @param        $lng2
     * @param  bool  $miles  是否是公里
     *
     * @return string
     */
    function get_distance_str($lng1, $lat1, $lng2, $lat2, $miles = true)
    {
        $dis = get_distance($lng1, $lat1, $lng2, $lat2, $miles);

        return $dis < 1 ? $dis * 1000 . 'm' : floor($dis) . 'km';
    }
}
if (!function_exists('get_square_point')) {
    /**
     * 围绕某个点，附近的人
     *
     * @param $lng      float 经度
     * @param $lat      float 纬度
     * @param $distance float 该点所在圆的半径，该圆与此正方形内切，默认值为单位公里
     *
     * @return array 正方形的四个点的经纬度坐标
     */
    function get_square_point(float $lng, float $lat, float $distance) : array
    {
        $PI        = 3.14159265;
        $longitude = $lng;
        $latitude  = $lat;

        $degree     = (24901 * 1609) / 360.0;
        $raidusMile = $distance * 1000;

        $dpmLat    = 1 / $degree;
        $radiusLat = $dpmLat * $raidusMile;
        $minLat    = $latitude - $radiusLat;       //拿到最小纬度
        $maxLat    = $latitude + $radiusLat;       //拿到最大纬度

        $mpdLng    = $degree * cos($latitude * ($PI / 180));
        $dpmLng    = 1 / $mpdLng;
        $radiusLng = $dpmLng * $raidusMile;
        $minLng    = $longitude - $radiusLng;     //拿到最小经度
        $maxLng    = $longitude + $radiusLng;     //拿到最大经度

        return array(
            'minLat' => $minLat,
            'maxLat' => $maxLat,
            'minLon' => $minLng,
            'maxLon' => $maxLng
        );
    }

    if (!function_exists('version_to_integer')) {

        /**
         * 版本号转数字
         *
         * @param $ver
         *
         * @return int
         */
        function version_to_integer($ver)
        {
            $ver = explode(".", $ver);
            if (count($ver) !== 3) {
                return 0;
            }

            $v1 = sprintf('%03s', (int)$ver[0] ?? 0);
            $v2 = sprintf('%03s', (int)$ver[1] ?? 0);
            $v3 = sprintf('%03s', (int)$ver[2] ?? 0);

            return (int)"{$v1}{$v2}{$v3}";
        }
    }

    if (!function_exists('integer_to_version')) {

        /**
         * 版本号转数字
         *
         * @param $versionCode
         *
         * @return int
         */
        function integer_to_version($ver)
        {
            if($ver > 999) {
                if($ver > 999999) {
                    $ver = $ver . "";
                    $v3 = (int) substr($ver, -3);
                    $v2 = (int) substr($ver, -6, 3);
                    $v1 = (int) substr($ver, 0, strlen($ver) - 6);
                } else {
                    $ver = $ver . "";
                    $v3 = (int) substr($ver, -3);
                    $v2 = (int) substr($ver, 0, strlen($ver) - 3);
                    $v1 = 0;
                }
            } else {
                $v3 = $ver;
                $v2 = 0;
                $v1 = 0;
            }
            return "{$v1}.{$v2}.{$v3}";
        }
    }

    if (!function_exists('arr_str_to_int')) {
        /**
         * 数组字符串转int字符串
         *
         * @param  array  $arrStr
         *
         * @return array
         */
        function arr_str_to_int($arrStr = [])
        {
            $ids = [];
            foreach ($arrStr as $key => $val) {
                $ids[$key] = (int)$val;
            }

            return $ids;
        }
    }

}


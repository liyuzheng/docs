<?php

if (!function_exists('get_millisecond')) {
    /**
     * Gets the number of milliseconds of the current time
     *
     * @return float
     */
    function get_millisecond()
    {
        list($t1, $t2) = explode(' ', microtime());

        return (int)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }
}

if (!function_exists('url_to_string')) {
    /**
     * Changes the argument to a string
     *
     * @param  array  $array_query
     *
     * @return string
     */
    function url_to_string(array $array_query)
    {
        $tmp = array();
        foreach ($array_query as $k => $param) {
            $tmp[] = $k . '=' . $param;
        }
        $params = implode('&', $tmp);

        return $params;
    }
}


if (!function_exists('convert_url_query')) {
    /**
     * Split get data
     *
     * @param  string  $query
     *
     * @return array
     */
    function convert_url_query($query)
    {
        $query_parts = explode('&', $query);
        $params      = [];
        foreach ($query_parts as $param) {
            $item             = explode('=', $param);
            $params[$item[0]] = $item[1];
        }

        return $params;
    }
}


if (!function_exists('get_request_value')) {
    /**
     * Gets a parameter from the last request
     *
     * @param  string  $key
     * @param  string  $default
     *
     * @return array|\Illuminate\Http\Request|string
     */
    function get_request_value(string $key, string $default = '')
    {
        return request()->has($key) ? request($key) : $default;
    }

}

if (!function_exists('aes_encrypt')) {
    /**
     * Encryption class single
     *
     * @return \App\Foundation\Handlers\AesEncryptHandler
     */
    function aes_encrypt()
    {
        return app('aes_encrypt');
    }
}

if (!function_exists('current_route_name')) {
    /**
     * Get current route name
     *
     * @return string
     */
    function current_route_name()
    {
        return request()->route()[1]['as'];
    }
}

if (!function_exists('get_md5_random_str')) {
    /**
     * Gets a random MD5 string
     *
     * @return string
     */
    function get_md5_random_str()
    {
        return md5(time() . uniqid() . str_random(16));
    }
}

if (!function_exists('get_url_query')) {
    /**
     * 将参数变为字符串
     *
     * @param $array_query
     *
     * @return string string 'm=content&c=index&a=lists&catid=6&area=0&author=0&h=0®ion=0&s=1&page=1' (length=73)
     */
    function get_url_query($array_query)
    {
        $tmp = array();
        foreach ($array_query as $k => $param) {
            $tmp[] = $k . '=' . $param;
        }
        $params = implode('&', $tmp);

        return $params;
    }
}

if (!function_exists('url_query_to_array')) {
    /**
     * 将字符串参数变为数组
     *
     * @param $query
     *
     * @return array
     */
    function url_query_to_array($query)
    {
        $query_parts = explode('&', $query);
        $params      = array();
        foreach ($query_parts as $param) {
            $item             = explode('=', $param);
            $params[$item[0]] = $item[1];
        }

        return $params;
    }
}

if (!function_exists('get_date')) {
    /**
     * 获得date类型的time
     *
     * @param  string  $format
     *
     * @return false|string
     */
    function get_date($format = 'Y-m-d H:i:s')
    {
        return date($format, time());
    }
}

if (!function_exists('get_date_time')) {
    /**
     * 获得date类型的time
     *
     * @param  string  $format
     *
     * @return false|string
     */
    function get_date_time($time, $format = 'Y-m-d H:i:s')
    {
        return date($format, $time);
    }
}

if (!function_exists('seconds2days')) {
    /**
     * 秒数转x天x时x分x秒
     *
     * @param $mysec
     *
     * @return string
     */
    function seconds2days($mysec)
    {
        $mysec = (int)$mysec;
        if ($mysec === 0) {
            return '未接通';
        }

        $mins  = 0;
        $hours = 0;
        $days  = 0;


        if ($mysec >= 60) {
            $mins  = (int)($mysec / 60);
            $mysec = $mysec % 60;
        }
        if ($mins >= 60) {
            $hours = (int)($mins / 60);
            $mins  = $mins % 60;
        }
        if ($hours >= 24) {
            $days  = (int)($hours / 24);
            $hours = $hours % 60;
        }

        $output = '';

        if ($days) {
            $output .= $days . " 天 ";
        }
        if ($hours) {
            $output .= $hours . " 小时 ";
        }
        if ($mins) {
            $output .= $mins . " 分钟 ";
        }
        if ($mysec) {
            $output .= $mysec . " 秒 ";
        }
        $output = rtrim($output);

        return $output;
    }
}

if (!function_exists('time_diff')) {
    /**
     * 秒数转x天x时x分x秒
     *
     * @param $timediff
     *
     * @return array
     */
    function time_diff($timediff)
    {
        //计算天数
        $days = intval($timediff / 86400);
        //计算小时数
        $remain = $timediff % 86400;
        $hours  = intval($remain / 3600);
        //计算分钟数
        $remain = $remain % 3600;
        $mins   = intval($remain / 60);
        //计算秒数
        $secs = $remain % 60;

        return array("day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs);
    }
}

if (!function_exists('check_url')) {
    function check_url($url)
    {
        try {
            $array = get_headers($url, 1);
        } catch (Exception $e) {
            return false;
        }

        return preg_match('/200/', $array[0]) ? true : false;
    }
}

/**
 * 代替each函数
 */
if (!function_exists('fun_adm_each')) {
    function fun_adm_each(&$array)
    {
        $res = array();
        $key = key($array);
        if ($key !== null) {
            next($array);
            $res[1] = $res['value'] = $array[$key];
            $res[0] = $res['key'] = $key;
        } else {
            $res = false;
        }

        return $res;
    }
}

if (!function_exists('has_http_https')) {

    /**
     * @param $url
     *
     * @return bool
     */
    function has_http_https($url)
    {
        if (strripos($url, 'https://') !== false) {
            return true;
        }

        if (strripos($url, 'http://') !== false) {
            return true;
        }

        return false;
    }
}

if (!function_exists('public_path')) {
    /**
     * Return the path to public dir
     *
     * @param  null  $path
     *
     * @return string
     */
    function public_path($path = null)
    {
        return rtrim(app()->basePath('public/' . $path), '/');
    }
}

if (!function_exists('get_command_output_date')) {
    /**
     * command 输出时间
     *
     * @return string
     */
    function get_command_output_date()
    {
        return '[' . date('Y-m-d H:i:s') . ']';
    }
}

if (!function_exists('base64_png_remove_head')) {
    /**
     * 替换掉base64中的头
     *
     * @param $base64
     *
     * @return mixed
     */
    function base64_png_remove_head($base64)
    {
        return str_replace('data:image/png;base64,', '', $base64);
    }
}


if (!function_exists('base64_encode_image')) {
    /**
     * 图片转base64
     *
     * @param  ImageFile String 图片路径
     *
     * @return 转为base64的图片
     */
    function base64_encode_image($ImageFile)
    {
        if (file_exists($ImageFile) || is_file($ImageFile)) {
            $image_info   = getimagesize($ImageFile);
            $image_data   = fread(fopen($ImageFile, 'r'), filesize($ImageFile));
            $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));

            return $base64_image;
        } else {
            return false;
        }
    }
}


if (!function_exists('get_url_path')) {
    /**
     * 获得网络地址图片url
     *
     * @param $url
     *
     * @return string
     */
    function get_url_path($url)
    {
        $info = pathinfo(parse_url($url, PHP_URL_PATH));

        return $info['dirname'] . '/' . $info['basename'];
    }
}

if (!function_exists('get_url_extension')) {
    /**
     * 获得网络地址图片url
     *
     * @param $url
     *
     * @return string
     */
    function get_url_extension($url)
    {
        $info = pathinfo(parse_url($url, PHP_URL_PATH));

        return isset($info['extension']) ? $info['extension'] : '';
    }
}

if (!function_exists('get_url_path')) {
    /**
     * 获得网络地址图片url
     *
     * @param $url
     *
     * @return string
     */
    function get_url_path($url)
    {
        $info = pathinfo(parse_url($url, PHP_URL_PATH));

        return $info['dirname'] . '/' . $info['basename'];
    }
}
if (!function_exists('text_encode')) {
    /**
     * @param $str
     *
     * @return mixed|string
     */
    function text_encode($str)
    {
        if (!is_string($str)) {
            return $str;
        }
        if (!$str || $str == 'undefined') {
            return '';
        }

        $text = json_encode($str); //暴露出unicode
        $text = preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i", function ($str) {
            return addslashes($str[0]);
        }, $text); //将emoji的unicode留下，其他不动，这里的正则比原答案增加了d，因为我发现我很多emoji实际上是\ud开头的，反而暂时没发现有\ue开头。

        return json_decode($text);
    }
}
/**
 *
 */
if (!function_exists('get_file_info')) {
    /**
     * 获得文件资源的详细信息
     *
     * @param $fileName
     *
     * @return mixed
     */
    function get_file_info($fileName)
    {
        if (!storage()->exists($fileName)) {
            return (object)[];
        }
        $size = getimagesize(public_path($fileName));

        return [
            'width'     => $size[0] ?? 0,
            'height'    => $size[1] ?? 0,
            'resource'  => $fileName,
            'preview'   => file_url($fileName),
            'size'      => $size['bits'] ?? 0,
            'mime_type' => mime_content_type(storage_path($fileName)),
            'ext'       => substr(strrchr($fileName, '.'), 1),
        ];
    }
}
if (!function_exists('is_odd')) {
    /**
     * 判断奇偶数
     *
     * @param $n
     *
     * @return int
     */
    function is_odd($n)
    {
        return $n & 1;
    }
}

if (!function_exists('read_xml')) {
    function read_xml($file)
    {
        $fh = fopen("$file", 'r') or die($php_errormsg);
        $simple = fread($fh, filesize("$file"));
        fclose($fh) or die($php_errormsg);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $simple, $vals, $index);
        xml_parser_free($p);
        $meta[status]     = $vals[$index[STATUS][0]][value];
        $meta[paiva]      = $vals[$index[PAIVA][0]][value];
        $meta[alkuaika]   = $vals[$index[ALKUAIKA][0]][value];
        $meta[loppuaika]  = $vals[$index[LOPPUAIKA][0]][value];
        $meta[tiedosto]   = $vals[$index[TIEDOSTO][0]][value];
        $meta[koko]       = $vals[$index[KOKO][0]][value];
        $meta[otsikko]    = $vals[$index[OTSIKKO][0]][value];
        $meta[tyyppi]     = $vals[$index[TYYPPI][0]][value];
        $meta[kohde]      = $vals[$index[KOHDE][0]][value];
        $meta[lahde]      = $vals[$index[LAHDE][0]][value];
        $meta[toimittaja] = $vals[$index[TOIMITTAJA][0]][value];
        $meta[teksti]     = $vals[$index[TEKSTI][0]][value];

        return $meta;
    }
}


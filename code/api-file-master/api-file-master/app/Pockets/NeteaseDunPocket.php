<?php


namespace App\Pockets;

use App\Foundation\Modules\Pocket\BasePocket;
use Illuminate\Support\Str;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use GuzzleHttp\Client;

class NeteaseDunPocket extends BasePocket
{
    protected $secretKey;
    protected $commonParams;
    protected $momentLogHandle;

    protected $moment_image_business_id;

    /**
     * NeteaseDun constructor.
     *
     * http://support.dun.163.com/documents/2018041901?docId=150425164856872960
     */
    public function __construct()
    {

        $this->secretKey = config('netease.keys.dun.secret_key');
        $this->commonParams();

        return $this;
    }

    /**
     * 计算参数签名
     * $params 请求参数
     * $secretKey secretKey
     */
    private function genSignature($params)
    {
        ksort($params);
        $buff = "";
        foreach ($params as $key => $value) {
            if ($value !== null) {
                $buff .= $key;
                $buff .= $value;
            }
        }
        $buff .= $this->secretKey;

        return md5($buff);
    }

    private function commonParams()
    {
        $params             = [
            'secretId'   => config('netease.keys.dun.secret_id'),
            'businessId' => config('netease.keys.dun.business_id'),
            'version'    => 'v3.1',
            'timestamp'  => time(),
            'nonce'      => Str::random(11)
        ];
        $this->commonParams = $params;

        return $this;
    }

    /**
     * 将输入数据的编码统一转换成utf8
     *
     * @params 输入的参数
     */
    function toUtf8($params)
    {
        $utf8s = array();
        foreach ($params as $key => $value) {
            $utf8s[$key] = is_string($value) ? mb_convert_encoding($value, "utf8", "auto") : $value;
        }

        return $utf8s;
    }

    /**
     * 易盾图片检测
     *
     * @param  array  $images
     * @param         $businessId
     * @param         $userUUid
     *
     * @return ResultReturn
     */
    public function checkImages(array $images, $businessId, $userUUid)
    {
        $params              = array_merge($this->commonParams, ['version' => 'v4', 'businessId' => $businessId]);
        $params["timestamp"] = time() * 1000;// time in milliseconds
        $params["nonce"]     = sprintf("%d", rand());
        $imageArr            = [];
        foreach ($images as $image) {
            $imageArr[] = [
                'name' => $image,
                'type' => 1,
                'data' => cdn_url($image)
            ];
        }
        $params['images']    = json_encode($imageArr);
        $params['account']   = $userUUid;
        $params              = $this->toUtf8($params);
        $params['signature'] = $this->genSignature($params);


        $api = 'http://as.dun.163yun.com/v4/image/check';

        $data = [
            'msg'          => '',
            'response'     => '',
            'check_status' => [],
        ];

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded; charset=UTF-8",
                'method'  => 'POST',
                'timeout' => 10,
                'content' => http_build_query($params),
            ),
        );

        $logInfo = [
            'params'  => $params,
            'options' => $options,
            'msg'     => ''
        ];
        try {
            $context = stream_context_create($options);
            $result  = file_get_contents($api, false, $context);
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
            logger()->setLogType('dun.checkImage')->error($e->getMessage(), $logInfo);

            return ResultReturn::failed($result['msg'], $result);
        }

        if ($result === false) {
            $msg = 'file_get_contents failed.';

            return ResultReturn::failed($msg, $data);
        }

        $response = json_decode($result, true);

        if ($response['code'] != 200) {
            $msg = "check error";

            return ResultReturn::failed($msg, $response);
        }

        foreach ($response['antispam'] as $item) {
            $data['check_status'][$item['name']] = $item['action'];
            $data['content'][$item['name']]      = (count($item['labels']) > 0) ? config('custom.netease_dun_image_mapping')[$item['labels'][0]['label']] : '';
        }

        return ResultReturn::success($data);
    }

    /**
     * 文本敏感词检查
     *
     * @param $dataId      随机字符串
     * @param $content     文本内容
     * @param $businessId  业务ID
     * @param $userUUid    用户uuid
     *
     * @return ResultReturn
     * https://as.dun.163yun.com/v3/text/check
     * http://support.dun.163.com/documents/2018041901?docId=150425947576913920
     */
    public function checkText($dataId, $content, $businessId, $userUUid)
    {
        $params              = array_merge($this->commonParams, ['dataId' => $dataId, 'content' => $content, 'businessId' => $businessId]);
        $params              = $this->toUtf8($params);
        $params['account']   = $userUUid;
        $params['signature'] = $this->genSignature($params);
        $api                 = 'https://as.dun.163yun.com/v3/text/check';
        $data                = [
            'msg'          => '',
            'response'     => '',
            'check_status' => 200,
        ];
        $options             = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'timeout' => 2,
                'content' => http_build_query($params),
            ),
        );

        $logInfo = [
            'params'  => $params,
            'options' => $options,
            'msg'     => ''
        ];

        try {
            $context = stream_context_create($options);
            $result  = file_get_contents($api, false, $context);
        } catch (\Exception $e) {
            $result['msg'] = $logInfo['msg'] = $e->getMessage();
            logger()->setLogType('dun.checkText')->error($e->getMessage(), $logInfo);

            return ResultReturn::failed($result['msg'], $result);
        }

        if ($result === false) {
            $msg = 'file_get_contents failed.';

            return ResultReturn::failed($msg, $data);
        }

        $response = json_decode($result, true);

        if (!isset($response['result']['action'])) {
            $msg = 'unknown index action';

            return ResultReturn::failed($msg, $data);
        }

        $data['msg']      = $content;
        $data['response'] = $response;

        if ($response['result']['action'] != 2) {
            $data['check_status'] = 100;
        } else {
            $data['check_status'] = 200;
        }

        return ResultReturn::success($data);
    }
}

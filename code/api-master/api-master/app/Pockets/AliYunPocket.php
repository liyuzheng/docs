<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use AlibabaCloud\Client\AlibabaCloud;
use GuzzleHttp\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class AliYunPocket extends BasePocket
{

    private static $appNameMap = [
        'xiaoquan'                   => 'FaceAuth',
        'com.vish.sdssdAmaziasudoku' => 'FaceAuth01'
    ];

    /**
     * 设置基础请求客户端
     *
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function setClient()
    {
        AlibabaCloud::accessKeyClient(config('custom.aliyun.common.face_auth.key'), config('custom.aliyun.common.face_auth.secret'))
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
    }

    /**
     * 获取人脸认证token&result
     *
     * @param  string  $type
     * @param  string  $bizId
     * @param  string  $imageUrl
     *
     * @return ResultReturn
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function getAuthResponse(string $type, string $bizId, string $imageUrl = '')
    {
        $this->setClient();
        switch ($type) {
            case 'token':
                $action = 'DescribeVerifyToken';
                break;
            case 'result':
                $action = 'DescribeVerifyResult';
                break;
            default:
                return ResultReturn::failed(trans('messages.request_not_found'));
        }

        $options = [
            'query' => [
                'RegionId' => "cn-hangzhou",
                'BizType'  => 'FaceAuth',
                'BizId'    => $bizId
            ],
        ];
        if ($action == 'DescribeVerifyToken') {
            $options['query']['FaceRetainedImageUrl'] = $imageUrl;
        }
        try {
            $response = AlibabaCloud::rpc()
                ->product('Cloudauth')
                ->version('2019-03-07')
                ->action($action)
                ->method('POST')
                ->host('cloudauth.aliyuncs.com')
                ->options($options)
                ->request()
                ->toArray();
        } catch (ClientException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        } catch (ServerException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        }

        if (!key_exists('VerifyToken', $response) && !key_exists('VerifyStatus', $response)) {
            return ResultReturn::failed($response['Message']);
        }

        return ResultReturn::success($response);
    }

    /**
     * 获取图片比对结果
     *
     * @param  string  $pic1
     * @param  string  $pic2
     *
     * @return ResultReturn
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function getCompareResponse(string $pic1, string $pic2)
    {
        $this->setClient();
        $action  = 'CompareFaces';
        $options = [
            'query' => [
                'RegionId'         => "cn-hangzhou",
                'TargetImageType'  => "FacePic",
                'SourceImageType'  => "FacePic",
                'SourceImageValue' => $pic1,
                'TargetImageValue' => $pic2,
            ],
        ];
        try {
            $response = AlibabaCloud::rpc()
                ->product('Cloudauth')
                ->scheme('https')
                ->version('2019-03-07')
                ->action($action)
                ->method('POST')
                ->host('cloudauth.aliyuncs.com')
                ->options($options)
                ->request()
                ->toArray();
        } catch (ClientException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        } catch (ServerException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        }

        if (!key_exists('Data', $response)) {
            return ResultReturn::failed($response['RequestId']);
        }

        return ResultReturn::success($response['Data']);
    }

    /**
     * 获取图片中活体人脸数据
     *
     * @param  string  $pic
     *
     * @return ResultReturn
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function getDetectFaceResponse(string $pic)
    {
        $this->setClient();
        $action  = 'DetectFaceAttributes';
        $options = [
            'query' => [
                'RegionId'      => "cn-hangzhou",
                'MaterialValue' => $pic
            ],
        ];
        try {
            $response = AlibabaCloud::rpc()
                ->product('Cloudauth')
                ->scheme('https')
                ->version('2019-03-07')
                ->action($action)
                ->method('POST')
                ->host('cloudauth.aliyuncs.com')
                ->options($options)
                ->request()
                ->toArray();
        } catch (ClientException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        } catch (ServerException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        }

        if (!key_exists('Data', $response)) {
            return ResultReturn::failed($response['RequestId']);
        }

        return ResultReturn::success($response['Data']);
    }

    /**
     * 获取增强版智能核身token
     *
     * @param $pic
     * @param $userId
     * @param $bizId
     * @param $metaInfo
     *
     * @return ResultReturn
     * @throws ServerException
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function smartAuthResponse($pic, $userId, $bizId, $metaInfo)
    {
        $this->setClient();
        $user = rep()->user->getById($userId);
        try {
            $result = AlibabaCloud::rpc()
                ->product('Cloudauth')
                ->scheme('https')
                ->version('2020-06-18')
                ->action('InitSmartVerify')
                ->method('POST')
                ->host('cloudauth.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId'       => "cn-hangzhou",
                        'OuterOrderNo'   => $bizId,
                        'Mode'           => "LOGIN_SAFE",
                        'CertType'       => "IDENTITY_CARD",
                        'MetaInfo'       => $metaInfo,
                        'Mobile'         => $user->mobile,
                        'FacePictureUrl' => $pic,
                        'SceneId'        => "2000238",
                    ],
                ])
                ->request()->toArray();
        } catch (ClientException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        } catch (ServerException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        }
        if ($result['Code'] != 200) {
            return ResultReturn::failed($result['Message']);
        }

        return ResultReturn::success($result);
    }

    /**
     * 获取增强版智能核身结果
     *
     * @param $certifyId
     *
     * @return ResultReturn
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function smartAuthResult($certifyId)
    {
        $this->setClient();
        try {
            $result = AlibabaCloud::rpc()
                ->product('Cloudauth')
                ->scheme('https')
                ->version('2020-06-18')
                ->action('DescribeSmartVerify')
                ->method('POST')
                ->host('cloudauth.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId'  => "cn-hangzhou",
                        'CertifyId' => $certifyId,
                        'SceneId'   => "2000238",
                    ],
                ])
                ->request()->toArray();
        } catch (ClientException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        } catch (ServerException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        }

        if ($result['Code'] != 200) {
            return ResultReturn::failed($result['Message']);
        }

        return ResultReturn::success($result);
    }
}

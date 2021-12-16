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
        AlibabaCloud::accessKeyClient(config('custom.aliyun.face_auth.key'), config('custom.aliyun.face_auth.secret'))
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
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

<?php


namespace App\Pockets;

use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class AliGreenPocket extends BasePocket
{
    public function setClient()
    {
        AlibabaCloud::accessKeyClient(config('custom.aliyun.face_auth.key'), config('custom.aliyun.face_auth.secret'))
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
    }

    /**
     * 人脸检索
     *
     * @param $uuid
     * @param $group
     *
     * @return ResultReturn
     */
    public function sfaceImageCompare($uuid, $group)
    {
        $this->setClient();
        $user = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$user) {
            return ResultReturn::failed(trans('messages.user_not_found'));
        }
        $facePic = rep()->facePic->m()->where('user_id', $user->id)->orderByDesc('id')->first();
        if (!$facePic) {
            return ResultReturn::failed(trans('messages.not_found_base_img'));
        }
        $tasks      = [
            [
                'dataId' => $uuid,
                'url'    => cdn_url($facePic->base_map),
                'extras' =>
                    [
                        'groupId' => $group
                    ]
            ]
        ];
        $clientInfo = [
            'userId'   => $user->uuid,
            'userNick' => $user->nickname,
            'userType' => $user->role
        ];
        $body       = [
            'scenes' => ['sface-n'],
            'tasks'  => $tasks
        ];
        try {
            $result = AlibabaCloud::roa()
                ->product('Green')
                ->scheme('https') // https | http
                ->version('2018-05-09')
                ->pathPattern('/green/image/scan')
                ->method('POST')
                ->options([
                    'query' => [
                        'ClientInfo' => json_encode($clientInfo),
                    ],
                ])
                ->body(json_encode($body))
                ->request();
        } catch (ClientException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        } catch (ServerException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        }

        return ResultReturn::success($result->toArray());
    }

    /**
     * 自定义人脸检索添加个体
     *
     * @param $uuid
     * @param $group
     *
     * @return ResultReturn
     */
    public function sfaceAddPerson($uuid, $group)
    {
        $this->setClient();
        $user = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$user) {
            return ResultReturn::failed(trans('messages.user_not_found'));
        }
        $clientInfo = [
            'userId'   => $user->uuid,
            'userNick' => $user->nickname,
            'userType' => $user->role
        ];
        $body       = [
            'groupIds' => [$group],
            'personId' => $uuid,
            'name'     => $user->nickname
        ];
        try {
            $result = AlibabaCloud::roa()
                ->product('Green')
                ->scheme('https') // https | http
                ->version('2018-05-09')
                ->pathPattern('/green/sface/person/add')
                ->method('POST')
                ->options([
                    'query' => [
                        'ClientInfo' => json_encode($clientInfo),
                    ],
                ])
                ->body(json_encode($body))
                ->request();
        } catch (ClientException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        } catch (ServerException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        }

        $data = $result->toArray();
        if ($data['code'] == 400 && $data['msg'] == 'The person is exist!') {
            return ResultReturn::failed(trans('messages.user_exist'), ['exist' => true]);
        }

        return ResultReturn::success($data);
    }

    /**
     * 自定义人脸检索删除个体
     *
     * @param $uuid
     *
     * @return ResultReturn
     */
    public function sfaceDelPerson($uuid)
    {
        $this->setClient();
        $user = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$user) {
            return ResultReturn::failed(trans('messages.user_not_found'));
        }
        $clientInfo = [
            'userId'   => $user->uuid,
            'userNick' => $user->nickname,
            'userType' => $user->role
        ];
        $body       = [
            'personId' => $uuid,
        ];
        try {
            $result = AlibabaCloud::roa()
                ->product('Green')
                ->scheme('https') // https | http
                ->version('2018-05-09')
                ->pathPattern('/green/sface/person/delete')
                ->method('POST')
                ->options([
                    'query' => [
                        'ClientInfo' => json_encode($clientInfo),
                    ],
                ])
                ->body(json_encode($body))
                ->request();
        } catch (ClientException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        } catch (ServerException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        }

        $data = $result->toArray();
        if ($data['code'] == 400 && $data['msg'] == 'The person is not exist!') {
            return ResultReturn::failed(trans('messages.user_not_found'), ['exist' => false]);
        }

        return ResultReturn::success($data);
    }

    /**
     * 自定义人脸检索获取个体信息
     *
     * @param $uuid
     *
     * @return ResultReturn
     */
    public function sfaceGetPerson($uuid)
    {
        $this->setClient();
        $user = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$user) {
            return ResultReturn::failed(trans('messages.user_not_found'));
        }
        $clientInfo = [
            'userId'   => $user->uuid,
            'userNick' => $user->nickname,
            'userType' => $user->role
        ];
        $body       = [
            'personId' => $uuid,
        ];
        try {
            $result = AlibabaCloud::roa()
                ->product('Green')
                ->scheme('https') // https | http
                ->version('2018-05-09')
                ->pathPattern('/green/sface/person')
                ->method('POST')
                ->options([
                    'query' => [
                        'ClientInfo' => json_encode($clientInfo),
                    ],
                ])
                ->body(json_encode($body))
                ->request();
        } catch (ClientException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        } catch (ServerException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        }
        $data = $result->toArray();
        if ($data['code'] != 200) {
            return ResultReturn::failed(trans('messages.http_code_not_200_tmpl'));
        }

        return ResultReturn::success($data);
    }

    /**
     * 自定义人脸检索添加人脸
     *
     * @param $uuid
     * @param $urls
     *
     * @return ResultReturn
     */
    public function sfaceAddFace($uuid, $urls)
    {
        $this->setClient();
        $user = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$user) {
            return ResultReturn::failed(trans('messages.user_not_found'));
        }
        $clientInfo = [
            'userId'   => $user->uuid,
            'userNick' => $user->nickname,
            'userType' => $user->role
        ];
        $body       = [
            'personId' => $uuid,
            'urls'     => array_map(function ($i) { return cdn_url($i); }, $urls)
        ];
        try {
            $result = AlibabaCloud::roa()
                ->product('Green')
                ->scheme('https') // https | http
                ->version('2018-05-09')
                ->pathPattern('/green/sface/face/add')
                ->method('POST')
                ->options([
                    'query' => [
                        'ClientInfo' => json_encode($clientInfo),
                    ],
                ])
                ->body(json_encode($body))
                ->request();
        } catch (ClientException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        } catch (ServerException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        }

        $data = $result->toArray();
        if ($data['code'] != 200) {
            return ResultReturn::failed(trans('messages.http_code_not_200_tmpl'));
        }

        return ResultReturn::success($data);
    }

    /**
     * 自定义人脸检索删除人脸
     *
     * @param $uuid
     * @param $faceIds
     *
     * @return ResultReturn
     */
    public function sfaceDelFace($uuid, $faceIds)
    {
        $this->setClient();
        $user = rep()->user->m()->where('uuid', $uuid)->first();
        if (!$user) {
            return ResultReturn::failed(trans('messages.user_not_found'));
        }
        $clientInfo = [
            'userId'   => $user->uuid,
            'userNick' => $user->nickname,
            'userType' => $user->role
        ];
        $body       = [
            'personId' => $uuid,
            'faceIds'  => $faceIds
        ];
        try {
            $result = AlibabaCloud::roa()
                ->product('Green')
                ->scheme('https') // https | http
                ->version('2018-05-09')
                ->pathPattern('/green/sface/face/delete')
                ->method('POST')
                ->options([
                    'query' => [
                        'ClientInfo' => json_encode($clientInfo),
                    ],
                ])
                ->body(json_encode($body))
                ->request();
        } catch (ClientException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        } catch (ServerException $e) {
            return ResultReturn::failed($e->getErrorMessage());
        }
        $data = $result->toArray();
        if ($data['code'] != 200) {
            return ResultReturn::failed(trans('messages.http_code_not_200_tmpl'));
        }

        return ResultReturn::success($data);
    }
}

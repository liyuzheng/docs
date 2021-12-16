<?php
/**
 * Created by PhpStorm.
 * User: ailuoy
 * Date: 2019/3/8
 * Time: 下午3:32
 */

namespace App\Pockets;


use App\Constant\NeteaseCustomCode;
use GuzzleHttp\Client;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class NeteasePocket extends BasePocket
{
    private static $guzzleInstance;

    /**
     * @return Client
     */
    public static function getGuzzleInstance()
    {
        if (!self::$guzzleInstance instanceof \GuzzleHttp\Client) {
            self::$guzzleInstance = new Client(['timeout' => 2]);
        }

        return self::$guzzleInstance;
    }


    /**
     * 初始化headers
     *
     * @return ResultReturn
     */
    private function initHeader()
    {
        $now     = strval(time());
        $nonce   = md5($now);
        $headers = [
            'AppKey'       => config('custom.netease.app_key'),
            'Nonce'        => $nonce,
            'CurTime'      => $now,
            'CheckSum'     => sha1(config('custom.netease.app_secret') . $nonce . $now),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        return ResultReturn::success($headers);
    }

    /**
     * 统一发送请求
     *
     * @param         $method
     * @param         $api
     * @param  array  $body
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function request($method, $api, array $body)
    {

        try {
            $body     = get_url_query($body);
            $client   = self::getGuzzleInstance();
            $response = $client->request($method, $api, [
                'headers' => $this->initHeader()->getData(),
                'body'    => $body,
                'timeout' => 3
            ]);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }
        $decodeResponse = json_decode($response->getBody()->getContents(), true);
        if ($response->getStatusCode() != 200) {
            return ResultReturn::failed(trans('messages.http_code_not_200_tmpl'),
                $decodeResponse);
        }
        if (isset($decodeResponse['code']) && $decodeResponse['code'] != 200) {
            return ResultReturn::failed('业务code不是200', $decodeResponse);
        }

        return ResultReturn::success($decodeResponse);
    }

    /**
     * 创建云信用户
     *
     * @param $uuid
     * @param $nickname
     * @param $token
     * @param $avatar
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * http://dev.netease.im/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID?#%E5%88%9B%E5%BB%BA%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID
     */
    public function userCreate(int $uuid, $nickname, $token, $avatar)
    {
        $method   = 'POST';
        $api      = 'https://api.netease.im/nimserver/user/create.action';
        $body     = [
            'accid' => $uuid,
            'name'  => $nickname,
            'token' => $token,
            'icon'  => $avatar,
        ];
        $response = $this->request($method, $api, $body);

        return $response;
    }

    /**
     * 获取用户名片
     *
     * @param  array  $ids
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * http://dev.netease.im/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E7%94%A8%E6%88%B7%E5%90%8D%E7%89%87?#%E8%8E%B7%E5%8F%96%E7%94%A8%E6%88%B7%E5%90%8D%E7%89%87
     */
    public function userGetUinfos(array $ids)
    {
        $method = 'POST';
        $api    = 'https://api.netease.im/nimserver/user/getUinfos.action';
        $body   = ['accids' => json_encode($ids)];

        return $this->request($method, $api, $body);
    }


    /**
     * 发送自定义消息
     *
     * @param         $sUUid
     * @param         $rUUid
     * @param  array  $body
     * @param  array  $extension
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     * https://dev.yunxin.163.com/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E6%B6%88%E6%81%AF%E5%8A%9F%E8%83%BD?#%E5%8F%91%E9%80%81%E6%99%AE%E9%80%9A%E6%B6%88%E6%81%AF
     */
    public function msgSendCustomMsg($sUUid, $rUUid, array $body, array $extension = [])
    {
        $method = config('netease.api.msg.sendMsg.method');
        $api    = config('netease.api.msg.sendMsg.api');
        $body   = [
            'from'     => $sUUid,
            'ope'      => 0,
            'to'       => $rUUid,
            'type'     => 100,
            'body'     => json_encode($body),
            'useYidun' => 0,
            'option'   => json_encode([
                'roam'       => false,
                'sendersync' => true,
            ])
        ];

        if ($extension) {
            if (isset($extension['option'])) {
                $_options = $extension['option'];
                $option   = json_decode($body['option'], true);
                foreach ($_options as $key => $_option) {
                    $option[$key] = $_option;
                }
                $body['option'] = json_encode($option);
            }

            if (isset($extension['payload'])) {
                $body['payload'] = $extension['payload'];
            }

            if (isset($extension['pushcontent'])) {
                $body['pushcontent'] = $extension['pushcontent'];
            }
        }

        return $this->request($method, $api, $body);
    }

    /**
     * 发送动态点赞消息
     *
     * @param         $sUUid
     * @param         $rUUid
     * @param  array  $body
     * @param  array  $extension
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     * https://dev.yunxin.163.com/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E6%B6%88%E6%81%AF%E5%8A%9F%E8%83%BD?#%E5%8F%91%E9%80%81%E6%99%AE%E9%80%9A%E6%B6%88%E6%81%AF
     */
    public function sendMomentLikeCountMsg($sUUid, $rUUid, array $body, array $extension = [])
    {
        $method = config('netease.api.msg.sendMsg.method');
        $api    = config('netease.api.msg.sendMsg.api');
        $body   = [
            'from'     => $sUUid,
            'ope'      => 0,
            'to'       => $rUUid,
            'type'     => 100,
            'body'     => json_encode($body),
            'useYidun' => 0,
            'option'   => json_encode([
                'roam'       => false,
                'sendersync' => true,
                'push'       => false,
                'badge'      => false
            ])
        ];

        if ($extension) {
            if (isset($extension['option'])) {
                $_options = $extension['option'];
                $option   = json_decode($body['option'], true);
                foreach ($_options as $key => $_option) {
                    $option[$key] = $_option;
                }
                $body['option'] = json_encode($option);
            }

            if (isset($extension['payload'])) {
                $body['payload'] = $extension['payload'];
            }

            if (isset($extension['pushcontent'])) {
                $body['pushcontent'] = $extension['pushcontent'];
            }
        }

        return $this->request($method, $api, $body);
    }

    /**
     * 发送普通消息
     *
     * @param         $sUUid
     * @param         $rUUid
     * @param         $message
     * @param  array  $extension
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     * http://dev.netease.im/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E6%B6%88%E6%81%AF%E5%8A%9F%E8%83%BD?#%E5%8F%91%E9%80%81%E6%99%AE%E9%80%9A%E6%B6%88%E6%81%AF
     */
    public function msgSendMsg($sUUid, $rUUid, $message, array $extension = [], $type = 0)
    {
        $body = ['msg' => urlencode(str_replace("\"", "'", $message))];

        return $this->msgSendPointToPoint($sUUid, $rUUid,
            json_encode($body), $extension, $type);
    }

    /**
     * 发送点对点消息
     *
     * @param         $sUid
     * @param         $rUid
     * @param         $attach
     * @param  array  $extension
     * @param  int    $type
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function msgSendPointToPoint($sUid, $rUid, $attach, $extension = [], $type = 0)
    {

        $method = config('netease.api.msg.sendMsg.method');
        $api    = config('netease.api.msg.sendMsg.api');
        $body   = [
            'from'     => $sUid,
            'ope'      => 0,
            'to'       => $rUid,
            'type'     => $type,
            'body'     => $attach,
            'useYidun' => 0,
            'option'   => json_encode(['roam' => false, 'sendersync' => true,])
        ];

        if ($extension) {
            if (isset($extension['option'])) {
                $_options = $extension['option'];
                $option   = json_decode($body['option'], true);
                foreach ($_options as $key => $_option) {
                    $option[$key] = $_option;
                }
                $body['option'] = json_encode($option);
            }

            if (isset($extension['payload'])) {
                $body['payload'] = $extension['payload'];
            }

            if (isset($extension['ext'])) {
                $body['ext'] = $extension['ext'];
            }
        }

        return $this->request($method, $api, $body);
    }

    /**
     * 发送普通图片
     *
     * @param         $sUid
     * @param         $rUid
     * @param         $imageBody
     * @param  array  $extension
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     * http://dev.netease.im/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E6%B6%88%E6%81%AF%E5%8A%9F%E8%83%BD?#%E5%8F%91%E9%80%81%E6%99%AE%E9%80%9A%E6%B6%88%E6%81%AF
     */
    public function msgSendImage($sUid, $rUid, array $imageBody, array $extension = [])
    {
        $method    = config('netease.api.msg.sendMsg.method');
        $api       = config('netease.api.msg.sendMsg.api');
        $imageInfo = getimagesize(file_url($imageBody['url']));
        $sendBody  = [
            'name' => $imageBody['name'],
            'md5'  => md5_file(file_url($imageBody['url'])),
            'url'  => cdn_url($imageBody['url']),
            'ext'  => pathinfo(file_url($imageBody['url']), PATHINFO_EXTENSION),
            'w'    => $imageInfo[0],
            'h'    => $imageInfo[1],
            'size' => intval($this->remote_filesize(file_url($imageBody['url']))),
        ];
        $body      = [
            'from'     => $sUid,
            'ope'      => 0,
            'to'       => $rUid,
            'type'     => 1,
            'body'     => urlencode(json_encode($sendBody)),
            'useYidun' => 0,
            'option'   => json_encode([
                'roam'       => false,
                'sendersync' => true,
            ])
        ];
        if ($extension) {
            if (isset($extension['option'])) {
                $_options = $extension['option'];
                $option   = json_decode($body['option'], true);
                foreach ($_options as $key => $_option) {
                    $option[$key] = $_option;
                }
                $body['option'] = json_encode($option);
            }

            if (isset($extension['payload'])) {
                $body['payload'] = $extension['payload'];
            }
        }

        return $this->request($method, $api, $body);
    }

    /**
     * 批量发送点对点普通消息
     *
     * @param         $sUid
     * @param  array  $rUids
     * @param         $message
     * @param  array  $extension
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     * http://dev.netease.im/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E6%B6%88%E6%81%AF%E5%8A%9F%E8%83%BD?#%E6%89%B9%E9%87%8F%E5%8F%91%E9%80%81%E7%82%B9%E5%AF%B9%E7%82%B9%E6%99%AE%E9%80%9A%E6%B6%88%E6%81%AF
     */
    public function msgSendBatchMsg($sUid, array $rUids, $message, array $extension = [])
    {
        $method = config('netease.api.msg.sendBatchMsg.method');
        $api    = config('netease.api.msg.sendBatchMsg.api');
        $body   = [
            'fromAccid' => $sUid,
            'toAccids'  => json_encode($rUids),
            'type'      => 0,
            'body'      => json_encode(['msg' => urlencode($message)]),
            'useYidun'  => 0,
            'option'    => json_encode(['sendersync' => false, 'roam' => false])
        ];

        if ($extension) {
            if (isset($extension['option'])) {
                $_options = $extension['option'];
                $option   = json_decode($body['option'], true);
                foreach ($_options as $key => $_option) {
                    $option[$key] = $_option;
                }
                $body['option'] = json_encode($option);
            }

            if (isset($extension['payload'])) {
                $body['payload'] = $extension['payload'];
            }
        }

        return $this->request($method, $api, $body);
    }

    /**
     * 修改用户信息
     *
     * @param         $uuid
     * @param         $nickname
     * @param         $avatar
     * @param  array  $mores
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * http://dev.netease.im/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID?#%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID%E6%9B%B4%E6%96%B0
     */
    public function userUpdateUinfo($uuid, $nickname = '', $avatar = '', $mores = [])
    {
        $method = config('netease.api.user.updateUinfo.method');
        $api    = config('netease.api.user.updateUinfo.api');
        $body   = [
            'accid' => $uuid
        ];
        if ($nickname) {
            $body['name'] = $nickname;
        }
        if ($avatar) {
            $body['icon'] = $avatar;
        }

        if ($mores) {
            foreach ($mores as $key => $more) {
                $body[$key] = $more;
            }
        }

        return $this->request($method, $api, $body);
    }

    /**
     * 修改用户token
     *
     * @param  integer  $uuid
     * @param  string   $token
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * https://dev.yunxin.163.com/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID?#%E6%9B%B4%E6%96%B0%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1token
     */
    public function userUpdate(int $uuid, string $token)
    {
        $method = config('netease.api.user.update.method');
        $api    = config('netease.api.user.update.api');
        $body   = [
            'accid' => $uuid,
            'token' => $token
        ];

        return $this->request($method, $api, $body);
    }

    /**
     * 封禁网易云通信ID
     *
     * @param $uuid
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     * http://dev.netease.im/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID?#%E5%B0%81%E7%A6%81%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID
     */
    public function userBlock($uuid)
    {
        $method = config('netease.api.user.block.method');
        $api    = config('netease.api.user.block.api');
        $body   = [
            'accid'    => $uuid,
            'needkick' => 'true'
        ];

        return $this->request($method, $api, $body);
    }

    /**
     * 解禁网易云通信ID
     *
     * @param $uuid
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * http://dev.netease.im/docs/product/IM%E5%8D%B3%E6%97%B6%E9%80%9A%E8%AE%AF/%E6%9C%8D%E5%8A%A1%E7%AB%AFAPI%E6%96%87%E6%A1%A3/%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID?#%E8%A7%A3%E7%A6%81%E7%BD%91%E6%98%93%E4%BA%91%E9%80%9A%E4%BF%A1ID
     */
    public function userUnblock($uuid)
    {
        $method = config('netease.api.user.unblock.method');
        $api    = config('netease.api.user.unblock.api');
        $body   = [
            'accid' => $uuid,
        ];

        return $this->request($method, $api, $body);
    }

    /**
     * 页面打开或弹框打开 通知
     * noticeType: 1:打折充值页面打开
     * showType: 0:任何页面都可以打开 1: 只在个人页面打开
     *
     * @param  int  $noticeType
     * @param  int  $showType
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function pageOpenOrAlertNotice($uuid, $noticeType, $showType = 0)
    {
        $data = [
            'type' => NeteaseCustomCode::PAGE_OPEN_NOTICE,
            'data' => ['show_type' => $showType, 'notice_type' => $noticeType]
        ];

        return $this->msgSendCustomMsg(config('custom.little_helper_uuid'),
            $uuid, $data, ['option' => ['push' => false, 'badge' => false]]);
    }

    /**
     * 发送系统消息
     *
     * @param  int     $uuid
     * @param  string  $message
     * @param  bool    $isQueue
     *
     * @return ResultReturn|\App\Foundation\Modules\ResultReturn\ResultReturnStructure
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendSystemMessage($uuid, $message, $isQueue = true)
    {
        $sender = config('custom.little_helper_uuid');

        if ($isQueue) {
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->netease, 'msgSendMsg', [$sender, $uuid, $message]);
        }

        return $this->msgSendMsg($sender, $uuid, $message);
    }

    /**
     * 上传云信图片并发送图片消息
     *
     * @param         $sUid
     * @param         $rUid
     * @param  array  $imageBody
     * @param  array  $extension
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function msgSendNeteasePic($sUid, $rUid, array $imageBody, array $extension = [])
    {
        $uploadResult = $this->uploadPng($imageBody['url']);
        $data         = $uploadResult->getData();
        $newUrl       = $data['url'];

        $method    = config('netease.api.msg.sendMsg.method');
        $api       = config('netease.api.msg.sendMsg.api');
        $imageInfo = getimagesize($newUrl);
        $sendBody  = [
            'name' => $imageBody['name'],
            'md5'  => md5_file($newUrl),
            'url'  => $newUrl,
            'ext'  => $imageInfo['mime'],
            'w'    => $imageInfo[0],
            'h'    => $imageInfo[1],
            'size' => $this->remote_filesize($newUrl),
        ];
        $body      = [
            'from'     => $sUid,
            'ope'      => 0,
            'to'       => $rUid,
            'type'     => 1,
            'body'     => urlencode(json_encode($sendBody)),
            'useYidun' => 0,
            'option'   => json_encode([
                'roam'       => false,
                'sendersync' => true,
            ])
        ];
        if ($extension) {
            if (isset($extension['option'])) {
                $_options = $extension['option'];
                $option   = json_decode($body['option'], true);
                foreach ($_options as $key => $_option) {
                    $option[$key] = $_option;
                }
                $body['option'] = json_encode($option);
            }

            if (isset($extension['payload'])) {
                $body['payload'] = $extension['payload'];
            }
        }

        return $this->request($method, $api, $body);
    }

    /**
     * 消息撤回
     *
     * @param       $msgId       撤回的消息ID
     * @param       $createTime  消息的创建时间
     * @param       $from        发送者ID
     * @param       $to          接收者ID
     * @param  int  $ignoreTime  是否忽略时间限制
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function recall($msgId, $createTime, $from, $to, $ignoreTime = 1)
    {
        $method = config('netease.api.msg.recall.method');
        $api    = config('netease.api.msg.recall.api');
        $body   = [
            'deleteMsgid' => $msgId,
            'timetag'     => $createTime,
            'type'        => 7,
            'from'        => $from,
            'to'          => $to,
            'ignoreTime'  => $ignoreTime
        ];

        return $this->request($method, $api, $body);
    }

    /**
     * 上传图片到云信服务器
     *
     * @param $url
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function uploadPng($url)
    {
        $uploadMethod = config('netease.api.upload.image.method');
        $uploadApi    = config('netease.api.upload.image.api');
        $url          = file_url($url);
        $body         = [
            'content' => urlencode(base64_encode(file_get_contents($url))),
            'type'    => 'image/png',
        ];

        return $this->request($uploadMethod, $uploadApi, $body);
    }

    /**
     * 获取远程图片大小
     *
     * @param          $url
     * @param  string  $user
     * @param  string  $pw
     *
     * @return int|mixed
     */
    private function remote_filesize($url)
    {
        ob_start();
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_exec($ch);
        curl_close($ch);
        $head = ob_get_contents();
        ob_end_clean();

        $regex = '/Content-Length:\s([0-9].+?)\s/';
        preg_match($regex, $head, $matches);

        return isset($matches[1]) ? $matches[1] : 0;
    }

    /**
     * 发送小提示
     *
     * @param $sender
     * @param $receiver
     * @param $showUUid
     * @param $content
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendChatTips($sender, $receiver, $showUUid, $content)
    {
        //        $data      = [
        //            'show_id' => $showUUid,
        //        ];
        //        $extension = ['option' => ['push' => false, 'badge' => false]];
        //        pocket()->netease->msgSendMsg($sender, $receiver, $content, array_merge(['ext' => json_encode($data)], $extension));
        pocket()->netease->msgSendMsg($sender, $receiver, $content);

        //        pocket()->netease->msgSendCustomMsg($sender, $receiver, $data, $extension);

        return ResultReturn::success([]);
    }
}

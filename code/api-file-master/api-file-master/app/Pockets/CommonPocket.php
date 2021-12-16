<?php


namespace App\Pockets;

use Carbon\Carbon;
use App\Jobs\SendNimMsgJob;
use Illuminate\Http\Response;
use App\Jobs\CommonQueueMoreByPocketJob;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class CommonPocket extends BasePocket
{
    /**
     * 根据pocket对象调用通用队列,如果队列必须要求有序执行的不要调用,这个队列不支持并发
     *
     * @param  BasePocket  $pocket pocket对象
     * @param  string      $method pocket方法
     * @param  array       $args 方法参数
     * @param  int         $delayTime 队列延迟时间
     *
     * @return ResultReturn
     */
    public function commonQueueMoreByPocketJob(BasePocket $pocket, string $method, array $args, int $delayTime = 0)
    {
        $job = (new CommonQueueMoreByPocketJob($pocket, $method, $args))
            ->onQueue('common_queue_more_by_pocket')
            ->delay(Carbon::now()->addSeconds($delayTime));
        dispatch($job);

        return ResultReturn::success($job);
    }

    /**
     * 同步冷起数据通用队列
     *
     * @param  BasePocket  $pocket
     * @param  string      $method
     * @param  array       $args
     * @param  int         $delayTime
     *
     * @return ResultReturn
     */
    public function clodStartSyncDataByPocketJob(BasePocket $pocket, string $method, array $args, int $delayTime = 0)
    {
        $job = (new CommonQueueMoreByPocketJob($pocket, $method, $args))
            ->onQueue('clod_start_sync_data_by_pocket')
            ->delay(Carbon::now()->addSeconds($delayTime));
        dispatch($job);

        return ResultReturn::success($job);
    }

    /**
     * 发送云信消息队列
     * TODO 增加mongo记录，暂时不做
     *
     * @param  BasePocket  $pocket pocket对象
     * @param  string      $method pocket方法
     * @param  array       $args 方法参数
     * @param  int         $delayTime 队列延迟时间
     *
     * @return ResultReturn
     */
    public function sendNimMsgQueueMoreByPocketJob(BasePocket $pocket, string $method, array $args, int $delayTime = 0)
    {
        $job = (new SendNimMsgJob($pocket, $method, $args))
            ->onQueue('send_nim_msg')
            ->delay(Carbon::now()->addSeconds($delayTime));
        dispatch($job);

        return ResultReturn::success($job);
    }

    /**
     * 调用控制器方法
     *
     * @param  string  $class 类名
     * @param  string  $method 类内的方法
     * @param  array   $params 类传递的参数，支持多个，按照顺序传入
     *
     * @return ResultReturn
     */
    public function callControllerMethod(string $class, string $method, array $params)
    {
        if (!class_exists($class) || !method_exists($class, $method)) {
            return ResultReturn::failed(trans('messages.check_action_is_right'), []);
        }
        $callParams = [];
        foreach ($params as $key => $val) {
            if (is_object($val)) {
                foreach ($val as $requestKey => $requestVal) {
                    $val->offsetSet($requestKey, $requestVal);
                }
            }
            $callParams[] = $val;
        }
        try {
            /** @var Response $result */
            $callResult = (new $class)->$method(...$callParams);
            $content    = $callResult->content();
            $result     = json_decode($content, true);

            return ResultReturn::success($result);
        } catch (\Exception $exception) {
            logger()->error($exception->getMessage());

            return ResultReturn::failed($exception->getMessage(), []);
        }
    }

    /**
     * 截取中文替换为***
     *
     * @param $string
     *
     * @return string
     */
    function substrCut($string)
    {
        $firstStr = mb_substr($string, 0, 1, 'utf-8');
        $lastStr  = mb_substr($string, -1, 1, 'utf-8');

        return $firstStr . '**********' . $lastStr;
    }

    /**
     * 根据客户端 appName 获得系统小助手uuid
     *
     * @param $appName
     *
     * @return string
     */
    public function getSystemHelperByAppName($appName)
    {
        $configs = rep()->config->getQuery()->whereIn('appname', [$appName, 'common'])
            ->where('key', 'system_helper')->get();
        $sender  = $configs->where('appname', $appName)->first();
        if (!$sender) {
            $sender = $configs->first();
        }

        return $sender->value;
    }

    /**
     * 通用提醒弹框
     *
     * @param          $type
     * @param          $browserType
     * @param          $url
     * @param          $canClose
     * @param  string  $title
     * @param  string  $content
     * @param  string  $btn1
     * @param  string  $btn2
     *
     * @return ResultReturn
     */
    public function currencyMention($type, $browserType, $url, $canClose, $title = '', $content = '', $btn1 = '', $btn2 = '')
    {
        switch ($type) {
            case 1:
                $data = [
                    'content_alert' => [
                        'title'   => $title,
                        'content' => $content,
                        'btns'    => [
                            [
                                'name' => $btn1,
                                'type' => 2,
                                'data' => [
                                    'browser_type' => $browserType,
                                    'url'          => $url
                                ]
                            ]
                        ]
                    ],
                    'picture_alert' => null
                ];
                if ($canClose) {
                    $data['content_alert']['btns'][] = [
                        'name' => $btn2,
                        'type' => 1,
                        'data' => (object)[]
                    ];
                }
                break;
            case 2:
                $data = [
                    'picture_alert' => [
                        'is_close' => $canClose,
                        'data'     => [
                            [
                                'browser_type' => $browserType,
                                'picture'      => cdn_url('uploads/user/avatar/e97825b54901f395e1e8458627b80602.jpeg'),
                                'url'          => $url
                            ],
                            [
                                'browser_type' => $browserType,
                                'picture'      => cdn_url('uploads/user/avatar/e97825b54901f395e1e8458627b80602.jpeg'),
                                'url'          => $url
                            ]
                        ]
                    ],
                    'content_alert' => null
                ];
                break;
        }
        $data = [
            'type' => $type,
            'data' => $data
        ];

        return ResultReturn::success($data);
    }
}

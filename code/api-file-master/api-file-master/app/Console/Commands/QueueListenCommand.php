<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 队列监听挤压数据上报
 * Class QueueListen
 * @package App\Console\Commands
 */
class QueueListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xiaoquan:queue_listen {default_count} {max_count}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '队列监听积压数据推送钉钉';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $args        = $this->arguments();
        $queueCounts = [];
        $redisConfig = config('database.redis.queue');
        $addr        = $redisConfig['host'] . ":" . $redisConfig['port'];
        $redis       = new \Redis();
        if (!$redis->connect($redisConfig['host'], $redisConfig['port']) || !$redis->auth($redisConfig['password']) || !$redis->select(5)) {
            return false;
        }

        $allKeys = $redis->keys("queues:*");

        foreach ($allKeys as $k => $queue) {
            $queueArr = explode(":", $queue);
            $last     = $queueArr[count($queueArr) - 1] ?? '';
            if (isset($queueArr[0]) && $queueArr[0] == 'queues') {
                if (!in_array($last, ['notify', "delayed", 'reserved'])) {
                    $queueCount          = $redis->lLen($queue);
                    $queueCounts[$queue] = [
                        "type"  => "default",
                        "count" => intval($queueCount),
                    ];

                    continue;
                }

                if (in_array($last, ['notify', "delayed", 'reserved'])) {
                    if ($last == "notify") {
                        $queueDelayedCount   = $redis->lLen($queue);
                        $queueCounts[$queue] = [
                            "type"  => "notify",
                            "count" => intval($queueDelayedCount),
                        ];
                        continue;
                    }
                    if ($last == "delayed") {
                        $queueDelayedCount   = $redis->zcard($queue);
                        $queueCounts[$queue] = [
                            "type"  => "delayed",
                            "count" => intval($queueDelayedCount),
                        ];
                        continue;
                    }
                    if ($last == "reserved") {
                        $queueDelayedCount   = $redis->zcard($queue);
                        $queueCounts[$queue] = [
                            "type"  => "reserved",
                            "count" => intval($queueDelayedCount),
                        ];
                        continue;
                    }
                }
            }

        }


        $defaultCount = $args['default_count'];
        $maxCount     = $args['max_count'];

        /** @var array 指定列积压的最大积压数量 $maxQueues  其他走默认值 */
        $maxQueues = [
            'common_queue_more_by_pocket' => $maxCount,
            'update_user_field_to_es'     => $maxCount
        ];

        $dingUrl = "https://oapi.dingtalk.com/robot/send?access_token=c8d84555a2b745d5786b021c7ea7c3effb7bf09537e2d07bc49779aade28d07c";
        foreach ($queueCounts as $queueName => $queueCount) {
            $count = (int)($queueCount['count'] ?? 0);
            $this->line($queueName . "_数量：" . $count);

            foreach ($maxQueues as $queueKey => $queueMaxCount) {
                if (str_contains($queueName, $queueKey)) {
                    $defaultCount = $queueMaxCount;
                }
            }
            if ($count <= $defaultCount) {
                continue;
            }
            $params = [
                $dingUrl,
                sprintf('[队列积压通知]-队列%s：有积压, 积压数量: %s', $queueName, $count)
            ];
            pocket()->dingTalk->sendSimpleMessage(...$params);
        }

    }
}

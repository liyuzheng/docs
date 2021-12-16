<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\EsGreetCountJob;
use Carbon\Carbon;

/**
 * 更新es打招呼的数量[生产每5分钟跑一次,测试一分钟跑一次]
 * Class FillCoordinateCommand
 * @package App\Console\Commandsa
 */
class UpdateEsGreetCountCommand extends Command
{
    protected $signature   = 'xiaoquan:es_greet_count';
    protected $description = '更新es打招呼的数量';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $crontabTime  = 5 * 60;
        $twoDaysTimes = time() - config('custom.greet.expire_time');
        $time         = $twoDaysTimes + $crontabTime;
        $client       = redis()->client();
        $expiredKeys  = $client->zRangeByScore(config('redis_keys.greets.key'), 0, $time);
        foreach ($expiredKeys as $value) {
            $delay = 0;
            [$userId, $targetUserId, $timestamp] = explode('_', $value);
            if ($timestamp > $twoDaysTimes) {
                $delay = (int)$timestamp - $twoDaysTimes;
            }
            $job = (new EsGreetCountJob($userId, $targetUserId, $timestamp))
                ->onQueue('es_greet_count')
                ->delay(Carbon::now()->addSeconds($delay));
            dispatch($job);
        }
        $client->zRemRangeByScore(config('redis_keys.greets.key'), 0, $time);
    }
}

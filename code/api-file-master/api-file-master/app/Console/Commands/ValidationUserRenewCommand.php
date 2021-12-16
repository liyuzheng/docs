<?php


namespace App\Console\Commands;


use App\Jobs\AppleIpaValidationJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ValidationUserRenewCommand extends Command
{
    protected $signature   = 'ht:validation_user_renew {interval} {delay}';
    protected $description = '获取到所有快到期的会员查看是否续费';

    public function handle()
    {
        $delay      = $this->argument('delay');
        $interval   = $this->argument('interval');
        $limit      = 100;
        $currentNow = time();
        $expiredAt  = $currentNow + $interval;
        $lastId     = 0;

        do {
            $records = rep()->memberRecord->getQuery()->where('id', '>', $lastId)
                ->where('next_cycle_at', '<=', $expiredAt)
                ->where('next_cycle_at', '!=', 0)->orderBy('id')
                ->limit($limit)->get();

            foreach ($records as $record) {
                $job       = new AppleIpaValidationJob($record->id);
                $residueAt = $record->next_cycle_at - $currentNow;
                $job->onQueue('apple_ipa_validation');
                if ($residueAt <= $delay) {
                    dispatch($job);
                } else {
                    $job->delay(Carbon::now()->addSeconds($record->next_cycle_at - $currentNow - $delay));
                    dispatch($job);
                }
            }

            $lastId = optional($records->last())->id;
        } while ($records->count() >= $limit);
    }
}

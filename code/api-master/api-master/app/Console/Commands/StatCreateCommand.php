<?php

namespace App\Console\Commands;


use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class StatCreateCommand extends Command
{
    protected $signature   = 'xiaoquan:stat_create {action}';
    protected $description = '命令集合';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $action = $this->argument('action');
        switch ($action) {
            case 'user_group_by_appname' :
                $statAppName = rep()->userDetail->getQuery()
                    ->select(['client_name', DB::raw('count(*) as total')])
                    ->where('client_name', '<>', '')
                    ->groupBy('client_name')
                    ->get();
                if (!$statAppName->count()) {
                    $this->error('没有数据');

                    return ResultReturn::failed('没有数据');
                }
                $date      = date('Y-m-d', Carbon::yesterday()->timestamp);
                $insertArr = [];
                $now       = time();
                foreach ($statAppName as $item) {
                    $insertArr[] = [
                        'date'       => $date,
                        'appname'    => $item->client_name,
                        'user_count' => $item->total,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
                rep()->statDailyAppName->m()->insert($insertArr);
                break;
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 修改位置
 * Class FillCoordinateCommand
 * @package App\Console\Commands
 */
class FixPositionCommand extends Command
{
    protected $signature   = 'xiaoquan:fix_position {userId} {lng} {lat}';
    protected $description = '修改位置';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $userId = $this->argument('userId');
        $lng    = $this->argument('lng');
        $lat    = $this->argument('lat');
        pocket()->account->updateLocation($userId, $lng, $lat);
    }
}

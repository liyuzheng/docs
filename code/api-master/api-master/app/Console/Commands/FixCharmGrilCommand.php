<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;

/**
 * 修复用户
 * Class FillCoordinateCommand
 * @package App\Console\Commands
 */
class FixCharmGrilCommand extends Command
{
    protected $signature   = 'xiaoquan:fix_charmgril';
    protected $description = '修复用户';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $users = rep()->user->m()->where('id', '>', 0)->get();
        foreach ($users as $user) {
            $isCharmGril = (int)pocket()->user->hasRole($user, Role::KEY_CHARM_GIRL);
            mongodb('user')->where('_id', $user->id)->update([
                'charm_girl' => $isCharmGril,
            ]);
            $this->line('用户id：' . $user->id . '-修复成功！');
        }
    }
}

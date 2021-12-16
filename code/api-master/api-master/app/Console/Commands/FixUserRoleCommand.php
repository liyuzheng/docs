<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Class FixUserRoleCommand
 * @package App\Console\Commands
 */
class FixUserRoleCommand extends Command
{
    protected $signature   = 'xiaoquan:fix_user_role';
    protected $description = '修复用户角色';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $roles = rep()->role->m()
            ->where('type', Role::TYPE_COMMON)
            ->pluck('id', 'key');
        rep()->user->m()
            ->chunk(1000, function ($users) use ($roles) {
                foreach ($users as $user) {
                    $userRoles = explode(',', $user->role);
                    foreach ($userRoles as $roleKey) {
                        $userRoleId = $roles[$roleKey];
                        $roleCount  = rep()->userRole->m()
                            ->where('user_id', $user->id)
                            ->where('role_id', $userRoleId)
                            ->count();
                        if ($roleCount != 1) {
                            try {
                                DB::transaction(function () use ($user, $userRoleId, $roleKey) {
                                    rep()->userRole->m()
                                        ->where('user_id', $user->id)
                                        ->where('role_id', $userRoleId)
                                        ->delete();
                                    pocket()->userRole->createUserRole($user, $roleKey);
                                });
                            } catch (\Exception $exception) {
                                $this->line('用户id:' . $user->id . ':failed');

                                return true;
                            }
                        }
                        $this->line('用户id:' . $user->id . ':success');
                    }
                }
            });
    }
}

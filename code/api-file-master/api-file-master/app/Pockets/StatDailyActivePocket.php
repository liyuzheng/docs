<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\Redis;
use App\Models\StatDailyActive;

class StatDailyActivePocket extends BasePocket
{
    /**
     * 异步增加用户注册总数
     *
     * @param  string  $type
     * @param  int     $timestamp
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function incrRegisterCount(string $type, int $timestamp)
    {
        /** @var StatDailyActive $statDailyActive */
        $statDailyActive = $this->getStatDailyActive($timestamp);
        switch ($type) {
            case 'simple_user':
                $statDailyActive->update([
                    'user_register_count' => DB::raw('user_register_count + 1'),
                    'register_count'      => DB::raw('register_count + 1'),
                ]);
                break;
            case 'charm_girl':
                $statDailyActive->update([
                    'charm_register_count' => DB::raw('charm_register_count + 1'),
                    'user_register_count'  => DB::raw('user_register_count - 1'),
                ]);
                break;
        }
    }

    /**
     * 统计用户活跃
     *
     * @param  User  $user
     * @param  int   $timestamp
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function incrActiveCount(User $user, int $timestamp)
    {
        $userRole = explode(',', $user->role);
        $isMember = rep()->member->m()->where('user_id', $user->id)
            ->where(DB::raw('start_at + duration'), '>=', time())->first();
        /** @var StatDailyActive $statDailyActive */
        $statDailyActive        = $this->getStatDailyActive($timestamp);
        $update['active_count'] = DB::raw('active_count + 1');

        if (in_array(Role::KEY_CHARM_GIRL, $userRole)) {
            if ($isMember) {
                $update['c_member_active_count'] = DB::raw('c_member_active_count + 1');
                $update['member_active_count']   = DB::raw('member_active_count + 1');
            } else {
                $update['charm_active_count'] = DB::raw('charm_active_count + 1');
            }
        } else {
            if ($isMember) {
                $update['t_member_active_count'] = DB::raw('t_member_active_count + 1');
                $update['member_active_count']   = DB::raw('member_active_count + 1');
            } else {
                $update['user_active_count'] = DB::raw('user_active_count + 1');
            }
        }

        $statDailyActive->update($update);
    }

    /**
     * 取消魅力女生时的操作
     *
     * @param  int  $timestamp
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function decrCharmGirlCount(int $timestamp)
    {
        $statDailyActive = $this->getStatDailyActive($timestamp);
        $statDailyActive->update([
            'charm_register_count' => DB::raw('charm_register_count - 1'),
            'user_register_count'  => DB::raw('user_register_count + 1'),
        ]);
    }

    /**
     * 加锁获取statDailyActive对象
     *
     * @param  int  $timestamp
     *
     * @return bool|\App\Models\StatDailyActive
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    private function getStatDailyActive(int $timestamp)
    {
        $todayStart = strtotime(date('Y-m-d', $timestamp));
        $lock       = new RedisLock(Redis::connection(), 'lock:daily:active', 3);

        return $lock->block(3, function () use ($todayStart) {
            $statDailyActive = rep()->statDailyActive->m()->where('date', date('Y-m-d',
                $todayStart))->first();

            if (!$statDailyActive) {
                $latest          = rep()->statDailyActive->m()->orderByDesc('id')->first();
                $statDailyActive = rep()->statDailyActive->m()->create([
                    'date'                 => date('Y-m-d', $todayStart),
                    'register_count'       => $latest->register_count ?? 0,
                    'charm_register_count' => $latest->charm_register_count ?? 0,
                    'user_register_count'  => $latest->user_register_count ?? 0,
                ]);
            }

            return $statDailyActive;
        });
    }
}

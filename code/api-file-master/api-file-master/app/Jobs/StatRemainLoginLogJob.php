<?php


namespace App\Jobs;


use Illuminate\Support\Facades\DB;
use App\Models\StatRemainLoginLog;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use Carbon\Carbon;

class StatRemainLoginLogJob extends Job
{
    protected $userId;
    protected $loginAt;
    protected $toDayTime;

    public function __construct($userId, $toDayTime, $loginAt)
    {
        $this->userId    = $userId;
        $this->toDayTime = $toDayTime;
        $this->loginAt   = $loginAt;
    }

    public function handle()
    {
        if (!pocket()->user->whetherUpdateOrPostUserLoginAtToRedis($this->userId, $this->toDayTime)) {
            return ResultReturn::failed('不更新数据');
        }

        $loginDate = date('Y-m-d', $this->loginAt);
        $startAt   = strtotime($loginDate);
        $endAt     = strtotime("$loginDate +1 day");
        $userId    = $this->userId;

        $loginAtCount = DB::table('stat_remain_login_log')
            ->where('user_id', $userId)
            ->whereBetween('login_at', [$startAt, $endAt])
            ->count();
        if ($loginAtCount) {
            return ResultReturn::failed('already existed' . $loginAtCount);
        }
        $user       = rep()->user->write()->find($userId);
        $userDetail = DB::table('user_detail')->select(['user_id', 'os'])->where('user_id', $userId)->first();
        switch (optional($userDetail)->os) {
            case 'android':
                $os = StatRemainLoginLog::OS_ANDROID;
                break;
            case 'ios':
                $os = StatRemainLoginLog::OS_IOS;
                break;
            default:
                $os = StatRemainLoginLog::OS_UNKNOWN;
                break;

        }
        $registerAt  = strtotime((string)$user->created_at);
        $remainDay   = intval(floor(($this->loginAt - $registerAt) / 86400) - 1);
        $loginLogArr = [
            'user_id'     => $userId,
            'os'          => $os,
            'login_at'    => $this->loginAt,
            'remain_day'  => $remainDay <= 0 ? 1 : $remainDay,
            'register_at' => $registerAt,
            'created_at'  => time()
        ];
        try {
            $response = DB::transaction(function () use ($userId, $loginLogArr) {
                return DB::table('stat_remain_login_log')->insertGetId($loginLogArr);
            });
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }

        pocket()->user->updateOrPostUserLoginAtToRedis($this->userId, $this->loginAt);
        pocket()->stat->statUserActive($userId, $this->loginAt);
        pocket()->common->commonQueueMoreByPocketJob(pocket()->discount,
            'activeGivingDiscountByInviteTest', [$userId]);

        return ResultReturn::success($response);
    }
}

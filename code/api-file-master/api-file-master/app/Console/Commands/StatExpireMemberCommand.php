<?php

namespace App\Console\Commands;


use App\Models\Card;
use App\Models\StatDailyMember;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StatExpireMemberCommand extends Command
{
    protected $signature = 'xiaoquan:stat_expire_member';
    protected $description = '统计昨日:到期未续费会员、在期会员、今日到期会员和增量总到期会员人数 每日00:00分执行';

    public function handle()
    {
        $todayStartAtStr = date('Y-m-d');
        $todayStartAt    = strtotime($todayStartAtStr);
        pocket()->statDailyMember->getOrCreateRecord($todayStartAtStr, StatDailyMember::OS_IOS, Card::LEVEL_WEEK);

        $updates = [];
        $updates = $this->getTimeBeforeExpireMembersGroupByLevel($todayStartAt, $updates);
        $updates = $this->getTimeBeforeValidMembersGroupByLevel($todayStartAt - 86400, $updates);
        $updates = $this->getExpireInTodayMembersGroupByLevel($updates);
        $updates = $this->getLessTomorrowExpireMembersGroupByLevel($updates);

        foreach ($updates as $os => $update) {
            foreach ($update as $level => $item) {
                rep()->statDailyMember->getQuery()->where('date', $todayStartAtStr)
                    ->where('os', $os)->where('level', $level)->update($item);
            }
        }
    }

    /**
     * 获取到期时间小于等于 $expiredAt 的会员人数
     *
     * @param  int    $expiredAt
     * @param  array  $appends
     *
     * @return array
     */
    private function getTimeBeforeExpireMembersGroupByLevel($expiredAt, $appends)
    {
        $expireUsersSubQuery = rep()->member->getQuery()->select('card_id', 'reg_os', DB::raw('count(*) as co'))
            ->join('user_detail', 'user_detail.user_id', 'member.user_id')
            ->where(DB::raw('start_at + duration'), '<', $expiredAt)
            ->groupBy('user_detail.reg_os', 'member.card_id');

        $expireUsers = $this->getUsersGroupByLevelByJoin($expireUsersSubQuery);

        return $this->formatUpdateFields($expireUsers, $appends, 'not_renewal_count');
    }

    /**
     * 获取到期时间大于 $expiredAt 的会员人数
     *
     * @param  int    $expiredAt
     * @param  array  $appends
     *
     * @return array
     */
    public function getTimeBeforeValidMembersGroupByLevel($expiredAt, $appends)
    {
        $validUsersSubQuery = rep()->member->getQuery()->select('card_id', 'reg_os', DB::raw('count(*) as co'))
            ->join('user_detail', 'user_detail.user_id', 'member.user_id')
            ->where(DB::raw('start_at + duration'), '>', $expiredAt)
            ->groupBy('user_detail.reg_os', 'member.card_id');

        $validUsers = $this->getUsersGroupByLevelByJoin($validUsersSubQuery);

        return $this->formatUpdateFields($validUsers, $appends, 'valid_count');
    }

    /**
     * 获取到期时间为今天 0-24 点中当会员人数
     *
     * @param  array  $appends
     *
     * @return array
     */
    public function getExpireInTodayMembersGroupByLevel($appends)
    {
        $todayStartAt             = strtotime(date('Y-m-d'));
        $todayExpireUsersSubQuery = rep()->member->getQuery()->select('card_id', 'reg_os', DB::raw('count(*) as co'))
            ->join('user_detail', 'user_detail.user_id', 'member.user_id')
            ->where(DB::raw('start_at + duration'), '>', $todayStartAt)
            ->where(DB::raw('start_at + duration'), '<', $todayStartAt + 86400)
            ->groupBy('user_detail.reg_os', 'member.card_id');

        $todayExpireUsers = $this->getUsersGroupByLevelByJoin($todayExpireUsersSubQuery);

        return $this->formatUpdateFields($todayExpireUsers, $appends, 'current_expired_count');
    }

    /**
     * 获取到期时间小于 明天0点的会员总数
     *
     * @param  array  $appends
     *
     * @return array
     */
    public function getLessTomorrowExpireMembersGroupByLevel($appends)
    {
        $todayStartAt                = strtotime(date('Y-m-d'));
        $todayAllExpireUsersSubQuery = rep()->member->getQuery()->select('card_id', 'reg_os', DB::raw('count(*) as co'))
            ->join('user_detail', 'user_detail.user_id', 'member.user_id')
            ->where(DB::raw('start_at + duration'), '<', $todayStartAt + 86400)
            ->groupBy('user_detail.reg_os', 'member.card_id');

        $todayAllExpireUsers = $this->getUsersGroupByLevelByJoin($todayAllExpireUsersSubQuery);

        return $this->formatUpdateFields($todayAllExpireUsers, $appends, 'expired_count');

    }

    public function formatUpdateFields($levels, $appends, $index)
    {
        $osSums = [];
        foreach ($levels as $level) {
            $appends[$level->reg_os][$level->level][$index] = DB::raw($index . ' + ' . $level->co);

            $osSums[$level->reg_os] = isset($osSums[$level->reg_os]) ? $osSums[$level->reg_os] + $level->co : $level->co;
        }

        foreach ($osSums as $os => $osSum) {
            $appends[$os][0][$index] = DB::raw($index . ' + ' . $osSum);
        }

        return $appends;
    }

    private function getUsersGroupByLevelByJoin(Builder $query)
    {
        return rep()->card->getQuery()->select('card.level', 'res.reg_os', DB::raw('sum(res.co) as co'))
            ->joinSub($query, 'res', 'res.card_id', 'card.id')
            ->groupBy('card.level', 'res.reg_os')->get();
    }
}

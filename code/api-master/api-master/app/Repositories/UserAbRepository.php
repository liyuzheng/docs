<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\User;
use App\Models\UserAb;
use App\Models\UserDetail;
use App\Jobs\ABTestJob;

/**
 * Class UserAbRepository
 * @package App\Repositories
 */
class UserAbRepository extends BaseRepository
{
    private const MEMBER_PRICE_TESTS = [
        6 => UserAb::TYPE_MEMBER_PRICE_TEST_B,
        7 => UserAb::TYPE_MEMBER_PRICE_TEST_C,
        8 => UserAb::TYPE_MEMBER_PRICE_TEST_D,
        9 => UserAb::TYPE_MEMBER_PRICE_TEST_A,
    ];

    /**
     * @return mixed|string
     */
    public function setModel()
    {
        return UserAb::class;
    }

    public function createMemberPriceTest(User $user)
    {
        $userIdLastChar = substr($user->id, -1, 1);
        $testType       = self::MEMBER_PRICE_TESTS[$userIdLastChar]
            ?? UserAb::TYPE_MEMBER_PRICE_TEST_A;

        rep()->userAb->getQuery()->create(
            ['user_id' => $user->id, 'type' => $testType]);
    }

    /**
     * 创建金币AB测试数据
     *
     * @param  User  $user
     */
    public function createGoldTradeAbTest(User $user)
    {
        $testTradeType = substr($user->id, -1, 1) == 9 ? UserAb::TYPE_GOLD_TRADE_TEST_B
            : UserAb::TYPE_GOLD_TRADE_TEST_A;

        rep()->userAb->getQuery()->create(
            ['user_id' => $user->id, 'type' => $testTradeType]);
    }

    /**
     * 创建用户半月会员AB测试数据
     *
     * @param  User        $user
     * @param  UserDetail  $userDetail
     */
    public function createUserHalfMonthMemberAbTest(User $user, UserDetail $userDetail)
    {
        if ($userDetail->getRawOriginal('reg_schedule') == UserDetail::REG_SCHEDULE_GENDER) {
            $testHalfMonthType = $user->getRawOriginal('gender') == User::GENDER_MAN
            && in_array(substr($user->id, -1, 1), [1, 2])
                ? UserAb::TYPE_HALF_MONTH_TEST_B : UserAb::TYPE_HALF_MONTH_TEST_A;

            rep()->userAb->getQuery()->create(
                ['user_id' => $user->id, 'type' => $testHalfMonthType]);
        }
    }

    /**
     * 用户注册时添加用户的邀请AB测试数据
     *
     * @param  User        $user
     * @param  UserDetail  $userDetail
     */
    public function createManUserInviteAbTest(User $user, UserDetail $userDetail)
    {
        $testInviteType = UserAb::TYPE_MAN_INVITE_TEST_A;
        if ($user->getRawOriginal('gender') == User::GENDER_MAN
            && version_compare($userDetail->getRawOriginal('reg_version'), '2.2.0', '>=')) {
            $userIdLastChar = substr($user->id, -1, 1);
            $userIdLastChar == 0 && $testInviteType = UserAb::TYPE_MAN_INVITE_TEST_B;
        }

        rep()->userAb->getQuery()->create(
            ['user_id' => $user->id, 'type' => $testInviteType]);

        $job = (new ABTestJob('register', $user->id))
            ->onQueue('ab_test_statics');
        dispatch($job);
    }

    /**
     * 用户完成邀请人填写后  如果当前用户不是邀请测试B类用户, 根据邀请人是否B类邀请用户修改用户AB测试数据
     *
     * @param  User  $inviter
     * @param  User  $beInviter
     */
    public function updateUserInviteAbTestByInvite(User $inviter, User $beInviter)
    {
        if ($beInviter->getRawOriginal('gender') == User::GENDER_MAN) {
            $beInviterDetail = rep()->userDetail->getQuery()->where('user_id',
                $beInviter->id)->first();
            if (version_compare($beInviterDetail->getRawOriginal('reg_version'), '2.2.0', '>=')) {
                $testRecords = $this->getQuery()->whereIn('user_id', [$beInviter->id, $inviter->id])
                    ->whereIn('type', [UserAb::TYPE_MAN_INVITE_TEST_A, UserAb::TYPE_MAN_INVITE_TEST_B])
                    ->get();

                $inviterTestRecord   = $testRecords->where('user_id', $inviter->id)->first();
                $beInviterTestRecord = $testRecords->where('user_id', $beInviter->id)->first();
                $inviterTestType     = $inviterTestRecord ? $inviterTestRecord->getRawOriginal('type')
                    : UserAb::TYPE_MAN_INVITE_TEST_A;
                if ($beInviterTestRecord->getRawOriginal('type') != $inviterTestType) {
                    $beInviterTestRecord->update(['type' => $inviterTestType]);
                }
            }
        }
    }

    /**
     * 获取用户邀请AB测试记录
     *
     * @param  User|int  $user
     *
     * @return \App\Models\UserAb|null
     */
    public function getUserInviteTestRecord($user)
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->getQuery()->where('user_id', $userId)
            ->whereIn('type', [UserAb::TYPE_MAN_INVITE_TEST_A, UserAb::TYPE_MAN_INVITE_TEST_B])
            ->first();
    }

    /**
     * @param $userId
     *
     * @return bool
     */
    public function isExceptGoldTradeUser($userId)
    {
        $exceptGoldTradeTypes = rep()->userAb->getQuery()
            ->where('user_id', $userId)->whereIn('type', [
                UserAb::TYPE_GOLD_TRADE_TEST_A,
                UserAb::TYPE_GOLD_TRADE_TEST_B,
                UserAb::TYPE_NEW_GOLD_TRADE_TEST_A,
                UserAb::TYPE_NEW_GOLD_TRADE_TEST_B
            ])->pluck('type')->toArray();

        return empty($exceptGoldTradeTypes)
            || !in_array(UserAb::TYPE_NEW_GOLD_TRADE_TEST_A,
                $exceptGoldTradeTypes);
    }

    /**
     * @param $userId
     *
     * @return int
     */
    public function getMemberPriceTestType($userId)
    {
        $type = rep()->userAb->getQuery()->where('user_id', $userId)
            ->whereIn('type', [
                UserAb::TYPE_MEMBER_PRICE_TEST_A,
                UserAb::TYPE_MEMBER_PRICE_TEST_B,
                UserAb::TYPE_MEMBER_PRICE_TEST_C,
                UserAb::TYPE_MEMBER_PRICE_TEST_D,
                UserAb::TYPE_MEMBER_PRICE_TEST_E,
            ])->first();

        return $type ? $type->type : UserAb::TYPE_MEMBER_PRICE_TEST_A;
    }
}

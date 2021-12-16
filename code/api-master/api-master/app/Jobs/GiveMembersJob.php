<?php


namespace App\Jobs;

use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Card;
use App\Models\Good;
use App\Models\MemberRecord;
use App\Models\TradePay;
use Illuminate\Support\Facades\DB;

/**
 * 赠送会员队列
 *
 * Class GiveMembersJob
 * @package App\Jobs
 */
class GiveMembersJob extends Job
{
    const TYPE_GIVE_MAN_REGISTER = 1;

    private $userId;
    private $type;

    /**
     * GiveMembersJob constructor.
     *
     * @param $userId
     * @param $type
     */
    public function __construct($userId, $type = self::TYPE_GIVE_MAN_REGISTER)
    {
        $this->userId = $userId;
        $this->type   = $type;
    }


    public function handle()
    {
        switch ($this->type) {
            case self::TYPE_GIVE_MAN_REGISTER:
                $giveResp = DB::transaction(function () {
                    $lockUser     = rep()->user->getQuery()->lockForUpdate()->find($this->userId);
                    $memberRecord = rep()->memberRecord->getQuery()->where('user_id', $lockUser->id)
                        ->where('status', MemberRecord::STATUS_GIVE_REGISTER)
                        ->first();

                    if (!$memberRecord) {
                        /** @var \App\Models\Card $card */
                        $card       = rep()->card->getQuery()->where('level', Card::LEVEL_MONTH)
                            ->where('continuous', 0)->first();
                        $customGood = new Good();
                        $customGood->setAttribute('related_type', Good::RELATED_TYPE_CARD);
                        $customGood->setAttribute('related_id', $card->id);
                        $customGood->setAttribute('price', 0);
                        $customGood->setAttribute('id', 0);
                        $customGood->syncOriginal();

                        $tradePayData = pocket()->tradePay->buildTradePayByUserAndGoods(
                            null, $lockUser, $customGood, '');
                        $successData  = ['os' => 0, 'channel' => 0, 'done_at' => time(), 'status' => 100];

                        $tradePay = rep()->tradePay->getQuery()->create(array_merge($tradePayData, $successData));
                        pocket()->trade->createRecord($lockUser, $tradePay);
                        $duration = $card->getDuration();

                        pocket()->member->createMemberByCard($lockUser, $tradePay, $duration,
                            '', 0, MemberRecord::STATUS_GIVE_REGISTER);

                        return ResultReturn::success(null);
                    }

                    return ResultReturn::failed("已领取过");
                });
                break;
            default:
                $giveResp = ResultReturn::failed("无效赠送类型");
        }

        if ($giveResp->getStatus()) {
            $user    = rep()->user->getQuery()->find($this->userId);
            $message = trans('messages.give_month_member', [], $user->language);
            $sender  = config('custom.little_helper_uuid');
            pocket()->netease->msgSendMsg($sender, $user->uuid, $message);
        }
    }
}

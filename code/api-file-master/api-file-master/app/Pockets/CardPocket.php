<?php


namespace App\Pockets;


use App\Models\BaseModel;
use App\Models\Card;

/**
 * Class CardPocket
 * @package App\Pockets
 */
class CardPocket extends BaseModel
{
    /**
     * 获取会员等级
     *
     * @param  int  $userId  用户id
     *
     * @return int|mixed
     */
    public function getMemberLevel(int $userId)
    {
        $member = rep()->member->getQuery()
            ->where('user_id', $userId)
            ->where('expired_at', '>', time())
            ->orderBy('created_at', 'asc')
            ->first();
        if (!$member) {
            return 0;
        }

        return rep()->card->m()
            ->where('id', $member->card_id)
            ->value('level');

    }

    /**
     * 获取多个会员等级
     *
     * @param  array  $userIds
     *
     * @return int|mixed
     */
    public function getMembersLevel(array $userIds)
    {
        $cards   = rep()->card->m()
            ->where('type', Card::TYPE_MEMBER)
            ->where('deleted_at', 0)
            ->get();
        $members = rep()->member->getQuery()
            ->whereIn('user_id', $userIds)
            ->where('expired_at', '>', time())
            ->get();
        $data    = [];
        foreach ($userIds as $userId) {
            $currentMember = $members->where('user_id', $userId)->sortBy('created_at')->first();
            $level         = $currentMember ? $cards->where('id', $currentMember->card_id)->first() : 0;
            $data[$userId] = $level ? $level->level : 0;
        }

        return $data;
    }
}

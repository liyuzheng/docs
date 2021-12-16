<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\TradeBuy;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Class TradeBuyRepository
 * @package App\Repositories
 */
class TradeBuyRepository extends BaseRepository
{
    /**
     * @return mixed|string
     */
    public function setModel()
    {
        return TradeBuy::class;
    }

    /**
     * 分页获取某个用户已解锁的魅力女生
     *
     * @param  User    $user
     * @param  string  $page
     *
     * @return array|\Illuminate\Database\Eloquent\Collection
     */
    public function getUnlockedUsers(User $user, $page)
    {
        $tradeIds   = $this->getTradeIdsByFields($user, 'target_user_id', 'user_id', $page);
        $usersQuery = rep()->user->getQuery()->select('user.id', DB::raw('trade_buy.id as buy_id'), 'user.uuid',
            'user.nickname', 'trade_buy.created_at', 'trade_buy.ori_amount', 'trade_buy.related_type');

        return $this->getTradeUsersByQueryJoin($usersQuery, $tradeIds, 'target_user_id');
    }

    /**
     * 分页获取解锁过某个魅力女生的男生
     *
     * @param  User    $user
     * @param  string  $page
     *
     * @return array|\Illuminate\Database\Eloquent\Collection
     */
    public function getUnlockedUserMans(User $user, $page)
    {
        $tradeIds   = $this->getTradeIdsByFields($user, 'user_id', 'target_user_id', $page);
        $usersQuery = rep()->user->getQuery()->select('user.id', DB::raw('trade_buy.id as buy_id'),
            'user.uuid', 'user.nickname', 'trade_buy.created_at', 'user.gender', 'user.birthday');

        return $this->getTradeUsersByQueryJoin($usersQuery, $tradeIds, 'user_id');
    }

    /**
     * 连表获取交易记录相关的用户
     *
     * @param  Builder  $query
     * @param  array    $tradeIds
     * @param  string   $joinField
     *
     * @return array|\Illuminate\Database\Eloquent\Collection
     */
    protected function getTradeUsersByQueryJoin(Builder $query, array $tradeIds, string $joinField)
    {
        return $query->join('trade_buy', 'user.id', 'trade_buy.' . $joinField)->whereIn('trade_buy.id', $tradeIds)
            ->when($tradeIds, function ($query) use ($tradeIds) {
                $query->orderByRaw(DB::raw('FIELD(trade_buy.id, ' . implode(',', $tradeIds) . ')'));
            })->get();
    }

    /**
     * 根据字段获取 被解锁人或解锁人 对应的解锁私聊和解锁微信的交易id
     *
     * @param  User    $user
     * @param  string  $groupField
     * @param  string  $userField
     * @param  string  $page
     *
     * @return array
     */
    protected function getTradeIdsByFields(User $user, string $groupField, string $userField, $page)
    {
        return rep()->tradeBuy->getQuery()->select(DB::raw('MAX(id) as id'))->where($userField, $user->id)
            ->whereIn('related_type', [TradeBuy::RELATED_TYPE_BUY_PRIVATE_CHAT, TradeBuy::RELATED_TYPE_BUY_WECHAT])
            ->groupBy($groupField)->orderBy('id', 'desc')->when($page, function ($query) use ($page, $groupField) {
                [$tradeId, $userId] = explode('-', $page);
                $query->where('id', '<', $tradeId)->where($groupField, '!=', $userId);
            })->limit(20)->get()->pluck('id')->toArray();
    }

}

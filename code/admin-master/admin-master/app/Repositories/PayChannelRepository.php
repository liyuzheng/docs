<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\PayChannel;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

/**
 * Class PayChannelRepository
 * @package App\Repositories
 */
class PayChannelRepository extends BaseRepository
{
    /**
     * @return mixed|string
     */
    public function setModel()
    {
        return PayChannel::class;
    }

    /**
     * 根据每个渠道的支付比例获取一个支付渠道
     *
     * @param  User    $user
     * @param  int     $type
     * @param  string  $os
     *
     * @return \App\Models\PayChannel
     */
    public function getPayChannelByRatio(User $user, $type, $os)
    {
        $topUpUser = rep()->user->getById($user->id, ['uuid']);
        if (in_array($topUpUser->uuid, [
            151974715487944704,//罗阳
            177734191587262464,//罗阳
            195484232231473152,//罗阳
            179180262775586816,//邹晶晶prod
            155873419320098816,
            211093799513231360,
            173462050297610240,
            152024085059076096
        ])) {
            return rep()->payChannel->getById(array_random([2, 3], 1)[0]);
        }
        $channels = $this->getQuery()->where('type', $type)
            ->where(function ($query) use ($os) {
                $query->where('os', $os)->orWhere('os', PayChannel::OS_COMMON);
            })->orderBy('ratio', 'asc')->get();

        $sumRatio        = 0;
        $cacheKey        = config('redis_keys.cache.pay_channel_cache');
        $currentAllCount = Redis::exists($cacheKey) ? Redis::hget($cacheKey, $os) : 0;
        foreach ($channels as $channel) {
            $sumRatio += (int)($channel->getRawOriginal('ratio') * 100);
            if ($currentAllCount % 100 < $sumRatio) {
                return $channel;
            }
        }

        return $channels->last();
    }
}

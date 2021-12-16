<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Blacklist;

class BlacklistRepository extends BaseRepository
{
    public function setModel()
    {
        return Blacklist::class;
    }

    /**
     * 根据用户ID或者 client id 获取拉黑记录详情
     *
     * @param  int     $userId
     * @param  string  $clientId
     *
     * @return \App\Models\Blacklist
     */
    public function getBlacklistInfo($userId, $clientId)
    {
        return $this->getQuery()->where(function ($query) use ($userId, $clientId) {
            $query->when($userId, function ($query) use ($userId) {
                $query->where(function ($query) use ($userId) {
                    $query->where('related_type', Blacklist::RELATED_TYPE_OVERALL)->where('related_id', $userId);
                });
            })->when($clientId, function ($query) use ($clientId) {
                $query->orWhere(function ($query) use ($clientId) {
                    $query->where('related_type', Blacklist::RELATED_TYPE_CLIENT)->where('related_id', $clientId);
                });
            });
        })->where(function ($query) {
            $query->where('expired_at', 0)->orWhere('expired_at', '>', time());
        })->first();
    }
}

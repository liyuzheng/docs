<?php


namespace App\Pockets;

use Illuminate\Support\Facades\DB;
use App\Foundation\Modules\Pocket\BasePocket;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;

class UserDetailPocket extends BasePocket
{
    /**
     * 根据clentId获得用户数量
     *
     * @param  string  $clientId
     *
     * @return int
     */
    public function getUserCountByClientId(string $clientId)
    {
        if (!$clientId) {
            return 0;
        }

        return rep()->userDetail->m()->where('client_id', $clientId)->count();
    }

    /**
     * 通过经纬度获取城市名称
     *
     * @param  float  $lng
     * @param  float  $lat
     *
     * @return HigherOrderBuilderProxy|mixed|string
     */
    public function getCityByLoc(float $lng, float $lat) : string
    {
        $city = '';
        if ($lng == 0 && $lat == 0) {
            return $city;
        }
        $earthAverageRadius = 6371.393;
        $cityItems          = rep()->area->m()
            ->selectRaw(DB::raw("*,ROUND($earthAverageRadius * 2 * ASIN(SQRT(POW(SIN(($lat * PI() / 180 - lat * PI() / 180) / 2),2 ) + COS($lat * PI() / 180) * COS(lat * PI() / 180) * POW(SIN(($lng * PI() / 180 - lng * PI() / 180) / 2),2))) * 1000) AS distance_um"))
            ->orderBy('distance_um', 'ASC')
            ->limit(2)
            ->get();

        foreach ($cityItems as $cityItem) {
            $address = $cityItem->merger_name ?? "北京市";
            $add     = explode(',', $address);
            if (count($add) >= 3) {
                $city = $add[2] ?? ($add[1] ?? "");
                if (!empty(($city))) {
                    return $city;
                }
            }
        }

        return $city;
    }

    /**
     * 根据城市名称获取城市id
     *
     * @param  string  $areaName
     *
     * @return
     */
    public function getAreaIdByName(string $areaName)
    {
        if (!$areaName) {
            return 0;
        }

        return rep()->area->m()->select(['id', 'level', 'lng', 'lat'])
            ->where('level', 2)
            ->where('shortname', $areaName)
            ->first();
    }

    /**
     * 获得邀请码
     *
     * @param  int  $userId
     *
     * @return int
     */
    public function getInviteCodeByUserId(int $userId) : int
    {
        return 10000000 + $userId;
    }

    /**
     * 根据经纬度计算城市id
     *
     * @param $lng
     * @param $lat
     *
     * @return array
     */
    public function getCityId($lng, $lat) : array
    {
        $cityName = $this->getCityByLoc($lng, $lat);
        $city     = rep()->area->m()->select(['id', 'level', 'name', 'pid'])
            ->where('name', $cityName)
            ->where('level', 2)
            ->first();
        $cityId   = $provinceId = 0;
        if ($city) {
            $cityId     = $city->id;
            $provinceId = $city->pid;
        }

        return [$cityId, $provinceId];
    }

    /**
     * 更新用户设备号
     *
     * @param  int     $userId
     * @param  string  $clientId
     *
     * @return bool|int
     */
    public function updateUserClientId(int $userId, string $clientId)
    {
        return rep()->userDetail->m()->where('user_id', $userId)->update(['client_id' => $clientId]);
    }

    /**
     * 更新appname
     *
     * @param  int     $userId      用户ID
     * @param  string  $clientName  appname
     *
     * @return bool|int
     */
    public function updateUserClientName(int $userId, string $clientName)
    {
        return rep()->userDetail->m()->where('user_id', $userId)->update(['client_name' => $clientName]);
    }
}

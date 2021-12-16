<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 填充历史用户城市id
 * Class FillCityIdToEsCommand
 * @package App\Console\Commands
 */
class FillCityIdToEsCommand extends Command
{
    protected $signature   = 'xiaoquan:fill_city_id_to_es';
    protected $description = '填充历史用户城市id';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $areas = rep()->area->m()->whereIn('level', [2])->get();
        rep()->userDetail->m()->orderBy('user_id', 'desc')->chunk(1000, function ($userDetails) use ($areas) {
            $udpateData = [];
            foreach ($userDetails as $userDetail) {
                $city = $areas->where('name', $userDetail->region)->first();
                if (!$city) {
                    continue;
                }
                $udpateData[] = [
                    'user_id'     => $userDetail->user_id,
                    'city_id'     => $city->id,
                    'province_id' => $city->pid,
                    'hide'        => 0
                ];
                mongodb('user')->where('_id', $userDetail->user_id)->update([
                    'city_id'     => $city->id,
                    'province_id' => $city->pid,
                    'hide'        => 0
                ]);
            }
            $result = pocket()->esUser->batchUpdateEsCity($udpateData);
            if ($result->getStatus()) {
                $this->line('1000条更新成功！');
            } else {
                $this->error('错误id' . $userDetails->pluck('user_id')->implode(','));
                $this->error($result->getMessage());
            }
        });
        $this->line('success！');
    }
}

<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class StatSmsRecallPocket extends BasePocket
{
    /**
     * 统计短信召回数据
     *
     * @param  int     $time
     * @param  string  $field
     * @param  int     $count
     *
     * @return ResultReturn
     */
    public function incrSmsRecall(int $time, string $field, int $count = 1)
    {
        $date       = date('Y-m-d', $time);
        $statRecall = rep()->statSmsRecall->m()->where('date', $date)->first();
        if (!$statRecall) {
            rep()->statSmsRecall->m()->create([
                'date' => $date
            ]);
        }
        rep()->statSmsRecall->m()->where('date', $date)->increment($field, 1);

        return ResultReturn::success([
            'time'  => $time,
            'field' => $field,
            'count'  => $count
        ]);
    }
}

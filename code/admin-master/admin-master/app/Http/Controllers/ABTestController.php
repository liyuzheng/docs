<?php


namespace App\Http\Controllers;


use App\Models\Card;

class ABTestController extends BaseController
{
    /**
     * 获取汇总的app统计
     * @return \Illuminate\Http\JsonResponse
     */
    public function ab()
    {
        $abs = mongodb('ab')->get();

        return api_rr()->getOK($abs);
    }

    /**
     * 获取明细
     * @return \Illuminate\Http\JsonResponse
     */
    public function abDetail()
    {
        $userType = request('user_type', 'a');
        $type     = request('type', 'discount');
        $sorts    = [
            Card::LEVEL_WEEK,
            Card::LEVEL_HALF_MONTH,
            Card::LEVEL_MONTH,
            Card::LEVEL_SEASON,
            Card::LEVEL_HALF_YEAR,
        ];
        $abs      = mongodb('ab_detail')
            ->whereIn('card_level', $sorts)//400周卡  700半月卡 100 月卡  200季卡   500半年卡
            ->when($userType === 'a', function ($q) {
                $q->where('type', 201);
            }, function ($q) {
                $q->where('type', 202);
            })
            ->when($type === 'discount', function ($q) {
                $q->where('day_type', 0);
            }, function ($q) {
                $q->where('discount', 0);
            })
            ->get();
        $data     = [];
        $dataInfo = [];
        if ($type === 'day_type') {
            foreach ($abs as $ab) {
                $data[$ab['day_type']][] = [
                    'card_level' => $ab['card_level'],
                    'count'      => $ab['count'],
                ];
            }
            foreach ($data as $day => $vals) {
                $tmp = [];
                foreach ($sorts as $sort) {
                    $tmpValue = collect($vals)->where('card_level', $sort)->first();
                    if ($tmpValue) {
                        $tmp[] = $tmpValue;
                    }
                }
                $dataInfo[] = [
                    'type'  => $day,
                    'value' => $tmp
                ];
            }
        }

        if ($type === 'discount') {
            foreach ($abs as $ab) {
                $data[$ab['discount']][] = [
                    'card_level' => $ab['card_level'],
                    'count'      => $ab['count'],
                ];
            }
            foreach ($data as $day => $vals) {
                $tmp = [];
                foreach ($sorts as $sort) {
                    $tmpValue = collect($vals)->where('card_level', $sort)->first();
                    if ($tmpValue) {
                        $tmp[] = $tmpValue;
                    }
                }
                $dataInfo[] = [
                    'type'  => $day,
                    'value' => $tmp
                ];
            }
        }

        return api_rr()->getOK($dataInfo);
    }
}

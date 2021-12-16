<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Currency;

class CurrencyRepository extends BaseRepository
{
    /**
     * @return mixed|string
     */
    public function setModel()
    {
        return Currency::class;
    }

    /**
     * 通过本次请求当客户端系统获取支付列表
     *
     * @param string $os 请求的系统 ios｜android
     *
     * @return \Illuminate\Support\Collection
     */
    public function getListByOs(string $os)
    {
        $pays = $this->getQuery()->select('product_id', 'platform','type', 'channel', 'rmb_amount', 'amount', 'is_default')
            ->where('os', $os)->get();

        return $pays->groupBy('platform');
    }
}

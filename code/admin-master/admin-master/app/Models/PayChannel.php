<?php


namespace App\Models;


class PayChannel extends BaseModel
{
    protected $table = 'pay_channel';

    const TYPE_PINGXX = 100; // ping++支付

    const OS_COMMON = 'common';

    /**
     * @param $params
     *
     * @return mixed
     */
    public function getParamsAttribute($params)
    {
        return json_decode($params, true);
    }
}

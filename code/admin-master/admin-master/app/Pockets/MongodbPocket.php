<?php


namespace App\Pockets;

use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class MongodbPocket extends BasePocket
{
    /**
     * request验证不过的请求记录到mongodb中观察是否有异常
     *
     * @param  array  $arr
     *
     * @return ResultReturn
     */
    public function postApiParameterError(array $arr)
    {
        mongodb('api_parameter_error')->insert($arr);

        return ResultReturn::success($arr);
    }
}

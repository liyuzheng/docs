<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class MongodbPocket extends BasePocket
{
    /**
     * 上传文件路径到mongo
     *
     * @param          $path
     * @param  int     $relatedId
     *
     * @param  string  $type
     * @param  bool    $checked
     *
     * @return bool
     */
    public function postFileUploadRecordToMongo($path, $relatedId = 0, $type = '', $checked = false)
    {

        $createData = [
            'type'       => $type,
            'related_id' => (int)$relatedId,
            'path'       => $path,
            'checked'    => $checked,
            'created_at' => time()
        ];
        if ($mongoId = mongodb('upload_file_record')->insertGetId($createData)) {
            return true;
        }

        return false;
    }

    /**
     * 更新mongo的活跃时间
     *
     * @param $userId int 用户id
     *
     * @return mixed
     */
    public function updateMongoActiveAt(int $userId) : int
    {
        mongodb('user')->where('_id', $userId)->update([
            'active_at' => time()
        ]);

        return $userId;
    }

    /**
     * 获得解锁退还的钻石数量
     *
     * @param  int  $userId
     *
     * @return int
     */
    public function getRefundLockedAmount(int $userId)
    {
        $user = mongodb('user')->select('mark')->where('_id', $userId)->first();
        if (!$user || !isset($user['mark']['refund_locked_amount'])) {
            return 0;
        }

        return $user['mark']['refund_locked_amount'];
    }

    /**
     * 自增refund_locked_amount
     *
     * @param  int  $userId
     * @param       $amount
     *
     * @return ResultReturn
     */
    public function incrRefundLockedAmount(int $userId, $amount)
    {
        $refundLockedAmount = pocket()->mongodb->getRefundLockedAmount($userId);
        $refundLockedAmount += $amount;
        mongodb('user')->where('_id', $userId)->update(['mark.refund_locked_amount' => $refundLockedAmount]);

        return ResultReturn::success(['refund_locked_amount' => $refundLockedAmount]);
    }

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

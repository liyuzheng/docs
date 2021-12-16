<?php


namespace App\Pockets;

use App\Foundation\Modules\Pocket\BasePocket;

class AdminPocket extends BasePocket
{
    /**
     * 获取用户token
     *
     * @param $adminId
     *
     * @return string
     */
    public function getUserToken($adminId)
    {
        $now         = time();
        $newTokenArr = [
            'admin_id' => $adminId,
            'update'   => $now + 86400 * 7,
            'delete'   => $now + 86400 * 14
        ];

        return $adminId . '.' . aes_encrypt()->encrypt($newTokenArr);
    }
}

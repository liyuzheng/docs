<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class BizKeyValidator implements Rule
{
    /**
     * 确定验证规则是否通过。
     *
     * @param  string  $attribute
     * @param  mixed   $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        list($type, $id) = explode(':', $value);
        switch ($type) {
            case 'auction_order':
                return (bool)repository()->auctionOrder->getOrderById($id, ['id']);
                break;
            default:
                return false;
                break;
        }

        return false;
    }

    /**
     * 获取验证错误消息。
     *
     * @return string
     */
    public function message()
    {
        return ':attribute 业务key不存在';
    }
}
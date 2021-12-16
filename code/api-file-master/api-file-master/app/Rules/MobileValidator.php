<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MobileValidator implements Rule
{
    /**
     * 判断验证规则是否通过
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     * @param $validator
     *
     * @return bool
     */
    public function validate($attribute, $value, $parameters, $validator)
    {
        return !strpos($value, ' ');
    }


    public function passes($attribute, $value)
    {
        return !strpos($value, ' ');
    }

    /**
     * 获取验证错误消息。
     *
     * @return string
     */
    public function message()
    {
        return 'mobile validate error';
    }
}

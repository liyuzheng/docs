<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class FileExistsValidator implements Rule
{
    /**
     * 判断验证规则是否通过。
     *
     * @param  string  $attribute
     * @param  mixed   $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return storage()->exists($value);
    }

    /**
     * 获取验证错误消息。
     *
     * @return string
     */
    public function message()
    {
        return 'Image resource does not exist';
    }
}

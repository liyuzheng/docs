<?php


namespace App\Http\Requests\Admin;


use App\Http\Requests\BaseRequest;

class BlacklistRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'type'   => 'required'
        ];
    }

    public function messages()
    {
        return [
            'type.required'   => '请输入封禁类型'
        ];
    }
}

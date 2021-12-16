<?php


namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;

class UserTagRequest extends BaseRequest
{
    public function rules()
    {
        return ['type' => 'required'];
    }

    public function messages()
    {
        return [
            'type.required' => '缺少tag类型'
        ];
    }
}
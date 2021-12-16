<?php


namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class AuthRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'user_name' => 'required',
            'password'  => 'required'
        ];
    }

    public function messages()
    {
        return [
            'user_name.required' => '缺少user_name',
            'password.required'  => '缺少password'
        ];
    }
}
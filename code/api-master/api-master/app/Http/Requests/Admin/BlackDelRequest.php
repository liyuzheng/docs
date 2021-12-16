<?php


namespace App\Http\Requests\Admin;


use App\Http\Requests\BaseRequest;

class BlackDelRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'id'   => 'required'
        ];
    }

    public function messages()
    {
        return [
            'type.required'   => '请输入要删除的ID¬'
        ];
    }
}

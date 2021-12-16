<?php


namespace App\Http\Requests\Stat;


use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StatRecallRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'type' => [
                'required',
                Rule::in(['installed', 'uninstall'])
            ]
        ];
    }

    public function messages()
    {
        return [
            'type.required' => '请输入类型',
            'type.in'       => '类型不正确',
        ];
    }
}

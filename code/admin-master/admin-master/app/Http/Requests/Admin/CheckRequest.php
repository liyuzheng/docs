<?php


namespace App\Http\Requests\Admin;


use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CheckRequest extends BaseRequest
{
    public function rules()
    {
        return ['type' => 'required', Rule::in(['auth', 'update'])];
    }

    public function messages()
    {
        return [
            'uuids.required' => '缺少type'
        ];
    }
}
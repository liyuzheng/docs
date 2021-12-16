<?php


namespace App\Http\Requests\Config;

use App\Http\Requests\BaseRequest;

class BanExactWorldRequest extends BaseRequest
{
    public function rules()
    {

        return [
            'version' => 'required|numeric'
        ];

    }

    public function messages()
    {
        return [
            'version.required' => trans('messages.need_version'),
            'version.numeric'  => trans('messages.version_type_error')
        ];
    }
}

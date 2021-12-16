<?php


namespace App\Http\Requests\Moment;

use App\Http\Requests\BaseRequest;

class MomentRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'key' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'key.required' => trans('messages.request_field_not_blank'),
        ];
    }
}

<?php


namespace App\Http\Requests\Uploads;


use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class RemoteSingleRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'url' => ['required'],
            'ext' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'url.required' => trans('messages.request_field_not_blank'),
            'ext.required' => trans('messages.request_field_not_blank')
        ];
    }
}

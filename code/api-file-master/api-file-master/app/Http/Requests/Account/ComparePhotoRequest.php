<?php


namespace App\Http\Requests\Account;

use App\Http\Requests\BaseRequest;

class ComparePhotoRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'photo' => 'required',
            'uuid'  => 'required'
        ];
    }

    public function messages()
    {
        return [
            'photo.required' => trans('messages.request_params_lack_error'),
            'uuid.required'  => trans('messages.request_params_lack_error')
        ];
    }
}

<?php


namespace App\Http\Requests\Moment;

use App\Http\Requests\BaseRequest;

class MomentStoreRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'content' => 'required',
            'photos'  => 'required',
        ];
    }

    public function messages()
    {
        return [
            'content.required' => trans('messages.need_content'),
            'photos.required'  => trans('messages.need_picture'),
        ];
    }
}

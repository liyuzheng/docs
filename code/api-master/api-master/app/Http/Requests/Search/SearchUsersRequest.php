<?php


namespace App\Http\Requests\Search;


use App\Http\Requests\BaseRequest;

class SearchUsersRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'mobile' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'mobile.required' => trans('messages.need_mobile')
        ];
    }
}

<?php


namespace App\Http\Requests\Account;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class SwitchTmpMsgRequest extends BaseRequest
{
    public function rules()
    {
        return ['status' => 'required', Rule::in([true, false])];
    }

    public function messages()
    {
        return [
            'status.required' => trans('messages.need_status'),
            'status.in'       => trans('messages.switch_type_error')
        ];
    }
}

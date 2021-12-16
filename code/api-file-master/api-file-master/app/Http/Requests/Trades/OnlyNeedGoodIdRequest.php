<?php


namespace App\Http\Requests\Trades;


use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class OnlyNeedGoodIdRequest extends BaseRequest
{
    public function rules()
    {
        if ($this->getRouteName() == 'api.v1.trades.native_web.pingxx.pay') {
            context()->set('user_agent_os', 'native_web');
            context()->set('user_agent_client_name', 'xiaoquan');
        }

        return [
            'good_id' => 'required|exists:goods,uuid',
        ];
    }

    public function messages()
    {
        return [
            'good_id.required' => trans('messages.need_transaction_good'),
            'good_id.exists'   => trans('messages.need_transaction_good'),
        ];
    }
}

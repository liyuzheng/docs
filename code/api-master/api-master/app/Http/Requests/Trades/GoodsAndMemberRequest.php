<?php


namespace App\Http\Requests\Trades;


use App\Http\Requests\BaseRequest;
use App\Models\Good;
use Illuminate\Validation\Rule;

class GoodsAndMemberRequest extends BaseRequest
{
    public function rules()
    {
        if ($this->getRouteName() == 'api.v1.trades.native_web.goods') {
            context()->set('user_agent_os', 'native_web');
            context()->set('user_agent_client_name', 'xiaoquan');
        }

        return [
            'type'     => [
                'sometimes',
                'string',
                Rule::in([Good::RELATED_TYPE_STR_CURRENCY, Good::RELATED_TYPE_STR_CARD])
            ],
            'platform' => Rule::in(array_keys(Good::PLATFORM_MAPPING))
        ];
    }

    public function messages()
    {
        return [
            'platform.in' => trans('messages.pay_platform_error'),
            'type.in'     => 'type.in',
            'type.string' => 'type.string',
        ];
    }
}

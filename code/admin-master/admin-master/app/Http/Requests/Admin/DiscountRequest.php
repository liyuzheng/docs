<?php


namespace App\Http\Requests\Admin;

use App\Models\Discount;
use Illuminate\Validation\Rule;
use App\Http\Requests\BaseRequest;

class DiscountRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'related_type' => ['required', Rule::in([Discount::RELATED_TYPE_MANUAL, Discount::RELATED_TYPE_INVITE])],
            'platform'     => ['required', Rule::in([Discount::PLATFORM_WEB, Discount::PLATFORM_ANDROID, Discount::PLATFORM_NATIVE_IOS_WEB])],
            'discount'     => 'required|max:1|min:0',
        ];
    }

    public function messages()
    {
        return [
            'related_type.required' => '缺少related_type',
            'related_type.in'       => 'related_type值不在范围内',
            'platform.required'     => '缺少platform',
            'platform.in'           => 'platform值不在范围内',
            'discount.required'     => '缺少discount',
            'discount.max'          => 'discount最大为1',
            'discount.min'          => 'discount最小为0',
        ];
    }
}

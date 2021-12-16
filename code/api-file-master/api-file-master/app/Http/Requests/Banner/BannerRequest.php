<?php


namespace App\Http\Requests\Banner;

use App\Http\Requests\BaseRequest;

class BannerRequest extends BaseRequest
{
    public function rules()
    {
        switch ($this->routeName) {
            case 'admin.banner.store':
                return [
                    'type'       => 'required',
                    'sort'       => 'required|int',
                    'value'      => 'required',
                    'os'         => 'required',
                    'expired_at' => 'required',
                    'resource'   => 'required',
                    'version'    => 'required',
                    'role'       => 'required',
                ];
            default:
                return [];
        }
    }

    public function messages()
    {
        switch ($this->routeName) {
            case 'admin.banner.store':
                return [
                    'type.required'       => '缺少type',
                    'sort.required'       => '缺少sort',
                    'value.required'      => '缺少value',
                    'os.required'         => '缺少os',
                    'expired_at.required' => '缺少expired_at,为0则永不过期',
                    'resource.required'   => '缺少resource',
                    'version.required'    => '缺少version',
                    'role.required'       => '缺少role,类型是数组',
                ];
            default:
                return [];
        }
    }
}

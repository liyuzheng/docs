<?php


namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class VersionRequest extends BaseRequest
{
    public function rules()
    {
        switch ($this->routeName) {
            case 'admin.version.store':
                return [
                    'appname'   => 'required',
                    'os'        => 'required',
                    'bundle_id' => 'required',
                    'version'   => 'required',
                    'notice'    => 'required',
                ];
                break;
            case 'admin.version.audit':
                return [
                    'audit' => 'required',
                ];
                break;
            default:
                return [];
                break;
        }
    }

    public function messages()
    {
        switch ($this->routeName) {
            case 'admin.version.store':
                return [
                    'appname.required'   => '缺少appname',
                    'os.required'        => '缺少os',
                    'bundle_id.required' => '缺少bundle_id',
                    'version.required'   => '缺少version',
                    'notice.required'    => '缺少notice'
                ];
                break;
            case 'admin.version.audit':
                return [
                    'audit.required' => '缺少audit'
                ];
                break;
            default:
                return [];
                break;
        }
    }
}

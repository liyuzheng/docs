<?php


namespace App\Http\Controllers;


use App\Http\Requests\Stat\StatRecallRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class StatController extends BaseController
{
    /**
     * 统计短信召回数据
     *
     * @param  StatRecallRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function smsRecall(StatRecallRequest $request)
    {
        $type = $request->get('type');
        switch ($type) {
            case 'installed':
                $field = 'installed_open_count';
                break;
            case 'uninstall':
                $field = 'uninstall_open_count';
                break;
            default:
                return api_rr()->postOK('ok');
                break;
        }
        pocket()->common->commonQueueMoreByPocketJob(
            pocket()->statSmsRecall,
            'incrSmsRecall',
            [time(), $field, 1]
        );

        return api_rr()->postOK('ok');
    }

    /**
     * 探针接口
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function managerRecord(Request $request)
    {
        $urlArr = parse_url(URL::full());
        $arr    = [
            'ip'         => get_client_real_ip(),
            'path'       => isset($urlArr['path']) ? $urlArr['path'] : '',
            'query'      => isset($urlArr['query']) ? $urlArr['query'] : [],
            'headers'    => request()->headers->all(),
            'body_query' => request()->query(),
            'body_post'  => request()->post(),
            'created_at' => time()
        ];
        mongodb('tanzhen')->insert($arr);

        return api_rr()->postOK([
            'token' => md5(Str::random(16))
        ]);
    }
}

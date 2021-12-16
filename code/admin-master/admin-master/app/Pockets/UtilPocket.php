<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use Illuminate\Support\Facades\URL;
use App\Foundation\Modules\ResultReturn\ResultReturnStructure;

class UtilPocket extends BasePocket
{
    /**
     * 通过workerId获得雪花uuid
     *
     * @return int
     */
    public function getSnowflakeId() : int
    {
        $snowflake = new \Godruoyi\Snowflake\Snowflake(rand(1, 500), rand(1, 500));

        return (int)$snowflake->id();
    }

    /**
     * 获得分页数据的返回的data
     *
     * @param $data
     * @param $nextPage
     *
     * @return array
     */
    public function getPaginateFinalData($data, $nextPage)
    {
        $fullUrl    = URL::full();
        $pageUrl    = pocket()->util->replaceReqUrlPage($fullUrl, $nextPage);
        $returnData = [
            'data'     => $data,
            'paginate' => [
                'page'          => (string)$nextPage,
                'next_page_url' => '/' . $pageUrl['next_page_url'],
                'pre_page_url'  => '/' . $pageUrl['pre_page_url'],
            ]
        ];

        return $returnData;
    }

    /**
     * 替换page
     *
     * @param $fullUrl
     * @param $page
     *
     * @return array
     */
    public function replaceReqUrlPage($fullUrl, $page)
    {
        $getQuery    = parse_url($fullUrl, PHP_URL_QUERY);
        $getQueryArr = $getQuery ? convert_url_query($getQuery) : [];

        $path                = parse_url($fullUrl, PHP_URL_PATH);
        $prePageUrl          = ltrim($path . '?' . url_to_string($getQueryArr), '/');
        $getQueryArr['page'] = $page;
        $path                = parse_url($fullUrl, PHP_URL_PATH);
        $nextPageUrl         = ltrim($path . '?' . url_to_string($getQueryArr), '/');

        return [
            'next_page_url' => $nextPageUrl,
            'pre_page_url'  => $prePageUrl
        ];
    }

    /**
     * 转换appendToUser的前置条件
     *
     * @param  array  $property
     *
     * @return array
     */
    public function conversionAppendToUserArgs(array $property)
    {
        $filterArr = [];
        foreach ($property as $key => $value) {
            if (is_numeric($key)) {
                $filterArr[$value] = [];
            } else {
                $filterArr[$key] = $value;
            }
        }

        return $filterArr;
    }

    /**
     * 获得ios审核手机号
     *
     * @return array|int[]
     */
    public function getIosAuditMobile() : array
    {
        return [17064679944, 17795239532, 17712341000, 17712340009];
    }

    /**
     * 获得测试手机账号
     *
     * @return int[]
     */
    public function getTestMobile() : array
    {
        return [17712340001, 17712340002, 17712340003, 17712340004];
    }

    /**
     * 获得IOS审核人员的UUID
     *
     * @return array
     */
    public function getIosAuditUUIds() : array
    {
        $mobiles = $this->getIosAuditMobile();
        $users   = rep()->user->getUserByMobiles($mobiles, ['uuid']);

        return $users->pluck('uuid')->toArray();
    }

    /**
     * 获得ios审核能查看的用户id
     *
     * @return array
     */
    public function getIosAuditUserListUUIds() : array
    {
        $cofigUUIDS = config('custom.ios_audit.users_list_uuids');
        $uuids      = [];
        foreach ($cofigUUIDS as $uuid) {
            $uuids[] = (int)$uuid;
        }
        $uuids[] = -1;

        return $uuids;
    }

    /**
     * Converts an XML string into an array
     *
     * @param  string  $xmlString
     *
     * @return array
     */
    public static function xmlToArray($xmlString)
    {
        $parameters = json_decode(
            json_encode(simplexml_load_string(
                    $xmlString,
                    'SimpleXMLElement',
                    LIBXML_NOCDATA)
            ), true);

        return $parameters;
    }
}

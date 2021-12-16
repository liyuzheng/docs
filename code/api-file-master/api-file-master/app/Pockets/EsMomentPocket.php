<?php


namespace App\Pockets;

use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Foundation\Modules\ResultReturn\ResultReturnStructure;
use App\Models\Moment;
use Illuminate\Support\Facades\DB;

class EsMomentPocket extends EsPocket
{

    protected function getIndexName() : string
    {
        return 'moment';
    }

    /**
     * 删除UserIndex
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function deleteIndex()
    {
        $params = ['index' => $this->getIndexName()];

        if ($this->getClient()->indices()->exists($params)) {
            $this->getClient()->indices()->delete($params);

            return ResultReturn::success($params);
        }

        return ResultReturn::success($params);
    }

    /**
     * 创建Index
     *
     * @param  false  $force
     *
     * @return ResultReturn
     */
    public function postIndex($force = false) : ResultReturn
    {
        $params = ['index' => $this->getIndexName()];
        if ($this->getClient()->indices()->exists($params)) {
            if (!$force) {
                return ResultReturn::failed("existing");
            }
            $this->deleteIndex();
        }
        $createRes = $this->getClient()->indices()->create(array_merge(
            $params,
            ['body' => ['settings' => $this->getSettingsConfig()]]
        ));

        return ResultReturn::success([$createRes]);
    }

    /**
     *
     * @return array
     */
    private function getMappingsConfig() : array
    {
        return [
            '_source'    => [
                'enabled' => true
            ],
            'properties' => [
                'moment_id'    => [
                    'type'  => 'long',
                    'index' => true
                ],
                'user_id'      => [
                    'type'  => 'long',
                    'index' => true
                ],
                'gender'       => [
                    'type'  => 'long',
                    'index' => true
                ],
                'location'     => [
                    'type'  => 'geo_point',
                    'index' => true,
                ],
                'city_id'      => [
                    'type'  => 'long',
                    'index' => true,
                ],
                'province_id'  => [
                    'type'  => 'long',
                    'index' => true,
                ],
                'topic_id'     => [
                    'type'  => 'long',
                    'index' => true,
                ],
                'check_status' => [
                    'type'  => 'long',
                    'index' => true,
                ],
                'sort'         => [
                    'type'  => 'long',
                    'index' => true,
                ],
                'like_count'   => [
                    'type'  => 'long',
                    'index' => true,
                ],
                'created_at'   => [
                    'type'  => 'long',
                    'index' => true,
                ],
                'deleted_at'   => [
                    'type'  => 'long',
                    'index' => true,
                ],
            ]
        ];
    }

    /**
     * 获得setting
     *
     * @return array
     */
    private function getSettingsConfig() : array
    {
        return [
            'number_of_shards'   => 5,
            'number_of_replicas' => 1,
            "index"              => [
                "max_result_window" => "10000000",
            ]
        ];
    }

    /**
     * 更新mapping
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function updateMapping()
    {
        $params = ['index' => $this->getIndexName()];
        if (!$this->getClient()->indices()->exists($params)) {
            $this->getClient()->indices()->create($params);
        }
        $this->getClient()->indices()->putMapping(array_merge($params, ['body' => $this->getMappingsConfig()]));

        return ResultReturn::success($params);
    }

    /**
     * 获得mapping
     *
     * @return array
     */
    public function getMappings() : array
    {
        $params = ['index' => $this->getIndexName()];

        return $this->getClient()->indices()->getMapping($params);
    }

    /**
     * 获得settings
     *
     * @return array
     */
    public function getSettings() : array
    {
        $params = ['index' => $this->getIndexName()];

        return $this->getClient()->indices()->getSettings($params);
    }

    /**
     * 新增动态到es
     *
     * @param  int  $momentId
     * @param  int  $lng
     * @param  int  $lat
     *
     * @return ResultReturn
     */
    public function postMomentToEs(int $momentId, $lng = 0, $lat = 0) : ResultReturn
    {
        $moment = rep()->moment->getById($momentId);
        if (!$moment) {
            return ResultReturn::failed(trans('messages.moment_not_found'));
        }
        $user = rep()->user->getById($moment->user_id);
        if (!$user) {
            return ResultReturn::failed(trans('messages.user_not_exist'));
        }
        [$cityId, $provinceId] = pocket()->userDetail->getCityId($lng, $lat);
        $params = [
            'index' => $this->getIndexName(),
            'id'    => $momentId,
            'body'  => [
                'moment_id'    => $momentId,
                'user_id'      => $user->id,
                'gender'       => $user->gender,
                'location'     => [
                    'lat' => (float)$lat,
                    'lon' => (float)$lng,
                ],
                'city_id'      => $cityId,
                'province_id'  => $provinceId,
                'topic_id'     => $moment->topic_id,
                'check_status' => Moment::CHECK_STATUS_DELAY,
                'sort'         => $moment->sort,
                'like_count'   => $moment->like_count,
                'created_at'   => time(),
                'deleted_at'   => 0,
            ]
        ];

        try {
            $response = $this->getClient()->index($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return ResultReturn::failed($e->getMessage(), ['code' => 3]);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage(), ['code' => 2]);
        }

        return ResultReturn::success($response);
    }

    /**
     * 从es中搜索动态
     *
     * @param  array  $sortArr
     * @param  array  $sortBy
     * @param  int    $from
     * @param  int    $size
     * @param  int    $gender
     * @param  int    $topicId
     * @param  bool   $checkStatus
     *
     * @return array
     */
    public function getNormalMomentIds(
        array $sortArr,
        array $sortBy,
        $from = 0,
        $size = 10,
        $gender = 0,
        $topicId = 0,
        $checkStatus = true
    ) : array {
        $match = [];
        $sortArr && $match[] = [
            'range' => [
                'sort' => [$sortArr['field'] => $sortArr['value']]
            ]
        ];
        $gender && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['gender' => $gender]]
                ]
            ]
        ];
        $topicId && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['topic_id' => $topicId]]
                ]
            ]
        ];
        $checkStatus && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['check_status' => Moment::CHECK_STATUS_PASS]]
                ]
            ]
        ];
        $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['deleted_at' => 0]]
                ]
            ]
        ];
        $params  = [
            'index' => $this->getIndexName(),
            'body'  => [
                'sort'  => [
                    [
                        $sortBy['field'] => ['order' => $sortBy['value']]
                    ]
                ],
                'from'  => $from,
                'size'  => $size,
                'query' => [
                    'bool' => ['filter' => $match]
                ],
            ]
        ];
        try {
            $response = $this->getClient()->search($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return [[], []];
        }
        if (!isset($response['hits']['hits'])) {
            return [[], []];
        }
        $hits       = $response['hits']['hits'];
        $momentData = [];
        foreach ($hits as $hit) {
            $momentData[$hit['_source']['moment_id']] = $hit['_source'];
        }

        return [array_column(array_column($hits, '_source'), 'moment_id'), $momentData];
    }

    /**
     * 从es中搜索附近的话题V2
     *
     * @param  array  $sortArr
     * @param  int    $from
     * @param  int    $size
     * @param  int    $lon
     * @param  int    $lat
     * @param  int    $gender
     * @param  int    $topicId
     * @param  bool   $checkStatus
     *
     * @return array
     */
    public function getLbsMomentIds(
        array $sortArr,
        $from = 0,
        $size = 10,
        $lon = 0,
        $lat = 0,
        $gender = 0,
        $topicId = 0,
        $checkStatus = true
    ) : array {
        if ($lat === 0 && $lon === 0) {
            $lat = 39.904989;
            $lon = 116.405285;
        }
        $match = [];
        $sortArr && $match[] = [
            'range' => [
                'sort' => [$sortArr['field'] => $sortArr['value']]
            ]
        ];
        $gender && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['gender' => $gender]]
                ]
            ]
        ];
        $topicId && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['topic_id' => $topicId]]
                ]
            ]
        ];
        $checkStatus && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['check_status' => Moment::CHECK_STATUS_PASS]]
                ]
            ]
        ];
        $match[]    = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['deleted_at' => 0]]
                ]
            ]
        ];
        $distance[] = [
            'geo_distance' => [
                "distance" => "50km",
                "location" => [
                    "lat" => (float)$lat,
                    "lon" => (float)$lon,
                ],
            ],
        ];
        $params     = [
            'index' => $this->getIndexName(),
            'body'  => [
                'sort'  => [
                    [
                        'created_at' => [
                            'order' => 'desc'
                        ]
                    ],
                    [
                        '_geo_distance' => [
                            "location"      => [(float)$lon, (float)$lat],
                            "order"         => "asc",
                            "mode"          => "min",
                            "distance_type" => "arc",
                            "unit"          => "m"
                        ]
                    ]
                ],
                'from'  => $from,
                'size'  => $size,
                'query' => [
                    'bool' => [
                        'filter' => array_merge($distance, $match)
                    ]
                ],
            ]
        ];
        try {
            $response = $this->getClient()->search($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return [[], []];
        }
        if (!isset($response['hits']['hits'])) {
            return [[], []];
        }
        $hits       = $response['hits']['hits'];
        $momentData = [];
        foreach ($hits as $hit) {
            $momentData[$hit['_source']['moment_id']] = $hit['_source'];
        }

        return [array_column(array_column($hits, '_source'), 'moment_id'), $momentData];
    }

    /**
     * 获得动态的信息
     *
     * @param $momentId
     *
     * @return array
     */
    public function getMomentByMomentId($momentId) : array
    {
        $params = [
            'index' => $this->getIndexName(),
            'id'    => (int)$momentId
        ];
        try {
            $content = $this->getClient()->get($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return [];
        }
        if (!isset($content['_source'])) {
            return [];
        }

        return $content['_source'];
    }


    /**
     * 更新某个字段到es
     *
     * @param  int    $momentId
     * @param  array  $updateField
     *
     * @return ResultReturn
     */
    public function updateMomentFieldToEs(int $momentId, array $updateField)
    {
        $moment = DB::table('moment')->where('id', $momentId)->first();
        if (!$moment) {
            return ResultReturn::failed(trans('messages.moment_not_found'), ['code' => 1]);
        }
        $esUser = $this->getMomentByMomentId($momentId);
        if (!$esUser) {
            return ResultReturn::failed(trans('messages.es_not_found_moment'), ['code' => 2]);
        }
        $params = [
            'index' => $this->getIndexName(),
            'id'    => $momentId,
            'body'  => [
                'doc' => $updateField
            ]
        ];
        try {
            $response = $this->getClient()->update($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return ResultReturn::failed($e->getMessage(), ['code' => 3]);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage(), ['code' => 4]);
        }

        return ResultReturn::success($response);
    }


    /**
     * 删除动态信息
     *
     * @param $momentId  int 动态id
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function deleteMomentByMomentId(int $momentId)
    {
        $params = [
            'index' => $this->getIndexName(),
            'id'    => $momentId
        ];

        try {
            $response = $this->getClient()->delete($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return ResultReturn::success($params);
        }

        return ResultReturn::success($response);
    }
}

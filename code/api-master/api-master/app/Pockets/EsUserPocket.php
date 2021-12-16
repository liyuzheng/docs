<?php


namespace App\Pockets;

use App\Models\User;
use GuzzleHttp\Client;
use Elasticsearch\ClientBuilder;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Foundation\Modules\ResultReturn\ResultReturnStructure;
use Illuminate\Support\Facades\DB;
use App\Jobs\UpdateUserInfoToMongoJob;

class EsUserPocket extends EsPocket
{

    protected function getIndexName()
    {
        return 'user_location';
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
     * @param  bool  $force
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function postIndex($force = false)
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
    private function getMappingsConfig()
    {
        $mapping = [
            '_source'    => [
                'enabled' => true
            ],
            'properties' => [
                'user_id'              => [
                    'type'  => 'long',
                    'index' => true
                ],
                'charm_girl'           => [
                    'type'  => 'long',
                    'index' => true
                ],
                'charm_girl_done_at'   => [
                    'type'  => 'long',
                    'index' => true
                ],
                'gender'               => [
                    'type'  => 'long',
                    'index' => true
                ],
                'location'             => [
                    'type'  => 'geo_point',
                    'index' => true,
                ],
                'upload_location'      => [
                    'type'  => 'long',
                    'index' => true,
                ],
                'active_at'            => [
                    'type'  => 'long',
                    'index' => true,
                ],
                'city_id'              => [
                    'type'  => 'long',
                    'index' => true,
                ],
                'province_id'          => [
                    'type'  => 'long',
                    'index' => true,
                ],
                'hide'                 => [
                    'type'       => 'long',
                    'index'      => true,
                    'null_value' => 0
                ],
                'is_member'            => [
                    'type'  => 'long',
                    'index' => true
                ],
                'greet_count_two_days' => [
                    'type'       => 'long',
                    'index'      => true,
                    'null_value' => 0
                ],
                'followed_count'       => [
                    'type'  => 'long',
                    'index' => true
                ],
                'destroy_at'           => [
                    'type'  => 'long',
                    'index' => true
                ],
                'created_at'           => [
                    'type'  => 'long',
                    'index' => true
                ],
            ]
        ];

        return $mapping;
    }

    /**
     * 获得setting
     *
     * @return array
     */
    private function getSettingsConfig()
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
    public function getMappings()
    {
        $params = ['index' => $this->getIndexName()];

        return $this->getClient()->indices()->getMapping($params);
    }

    /**
     * 获得settings
     *
     * @return array
     */
    public function getSettings()
    {
        $params = ['index' => $this->getIndexName()];

        return $this->getClient()->indices()->getSettings($params);
    }

    /**
     * 从es中搜索附近的用户
     *
     * @param  int     $from
     * @param  int     $size
     * @param  int     $lon
     * @param  int     $lat
     * @param  int     $charmGril
     * @param  int     $gender
     * @param  int     $uploadLocation
     * @param  string  $sort
     * @param  int     $cityId
     * @param  int     $hide
     * @param  int     $isMember
     * @param  string  $version
     * @param  array   $excludeUsersId
     * @param  array   $sortArr
     *
     * @return array
     */
    public function getUsersIdByDistanceAndActive(
        $from = 0,
        $size = 10,
        $lon = 0,
        $lat = 0,
        $charmGril = 0,
        $gender = 0,
        $uploadLocation = 0,
        $sort = 'common',
        $cityId = 0,
        $hide = 0,
        $isMember = 0,
        $version = '1.0.0',
        $startDistance = '',
        $endDistance = '',
        $startActive = 0,
        $endActive = 0,
        array $excludeUsersId = [],
        $sortArr = []
    ) : array {
        if ($lat == 0 && $lon == 0) {
            $lat = 39.904989;
            $lon = 116.405285;
        }
        $match   = [];
        $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['destroy_at' => 0]]
                ]
            ]
        ];
        $gender && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['gender' => $gender]]
                ]
            ]
        ];
        $charmGril && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['charm_girl' => $charmGril]]
                ]
            ]
        ];
        $uploadLocation && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['upload_location' => $uploadLocation]]
                ]
            ]
        ];
        $match[] = [
            'range' => [
                'active_at' => ['gte' => $startActive, 'lte' => $endActive]
            ]
        ];
        $cityId && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['city_id' => $cityId]]
                ]
            ]
        ];
        $match[]    = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['hide' => $hide]]
                ]
            ]
        ];
        $distance[] = [
            'geo_distance' => [
                "distance" => $endDistance,
                "location" => [
                    "lat" => (float)$lat,
                    "lon" => (float)$lon,
                ],
            ],
        ];
        $isMember   = (int)$isMember;
        $isMember === 1 && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['is_member' => $isMember]]
                ]
            ]
        ];
        $sort === 'new_user' && $lbsSort = [
            'created_at' => ['order' => 'DESC']
        ];
        $sort === 'charm_first' && $lbsSort = [
            'followed_count' => ['order' => 'DESC']
        ];
        $params = [
            'index' => 'user_location',
            'body'  => [
                'sort'  => $sortArr,
                'from'  => $from,
                'size'  => $size,
                'query' => [
                    'bool' => [
                        'filter'   => array_merge($distance, $match),
                        'must_not' => [
                            [
                                'terms' => [
                                    '_id' => array_merge($excludeUsersId, pocket()->user->getLBSNotUserId()),
                                ]
                            ],
                            [
                                'geo_distance' => [
                                    "distance" => $startDistance,
                                    "location" => [
                                        "lat" => (float)$lat,
                                        "lon" => (float)$lon,
                                    ],
                                ],
                            ]
                        ]
                    ]
                ],
            ]
        ];
        try {
            $response = $this->getClient()->search($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return [];
        }
        if (!isset($response['hits']['hits'])) {
            return [];
        }
        $hits = $response['hits']['hits'];

        return array_column(array_column($hits, '_source'), 'user_id');
    }

    /**
     * 从es中搜索附近的用户
     *
     * @param  int     $from
     * @param  int     $size
     * @param  int     $lon
     * @param  int     $lat
     * @param  int     $charmGril
     * @param  int     $gender
     * @param  int     $uploadLocation
     * @param  string  $sort
     * @param  int     $cityId
     * @param  int     $hide
     * @param  int     $isMember
     * @param  string  $version
     *
     * @return array
     */
    public function getSearchLocationUsersIdByUserId(
        $from = 0,
        $size = 10,
        $lon = 0,
        $lat = 0,
        $charmGril = 0,
        $gender = 0,
        $uploadLocation = 0,
        $sort = 'common',
        $cityId = 0,
        $hide = 0,
        $isMember = 0,
        $version = '1.0.0'
    ) : array {
        if ($lat == 0 && $lon == 0) {
            $lat = 39.904989;
            $lon = 116.405285;
        }
        $match   = [];
        $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['destroy_at' => 0]]
                ]
            ]
        ];
        $gender && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['gender' => $gender]]
                ]
            ]
        ];
        $charmGril && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['charm_girl' => $charmGril]]
                ]
            ]
        ];
        $uploadLocation && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['upload_location' => $uploadLocation]]
                ]
            ]
        ];
        $sort === 'except_inactive' && $match[] = [
            'range' => [
                'active_at' => ['gte' => time() - config('custom.sort_active_time')]
            ]
        ];
        $cityId && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['city_id' => $cityId]]
                ]
            ]
        ];
        $match[]    = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['hide' => $hide]]
                ]
            ]
        ];
        $distance[] = [
            'geo_distance' => [
                "distance" => $cityId ? "5000km" : "100km",
                "location" => [
                    "lat" => (float)$lat,
                    "lon" => (float)$lon,
                ],
            ],
        ];
        $isMember   = (int)$isMember;
        $isMember === 1 && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['is_member' => $isMember]]
                ]
            ]
        ];
        $lbsSort = [
            '_geo_distance' => [
                "location"      => [(float)$lon, (float)$lat],
                "order"         => "asc",
                "mode"          => "min",
                "distance_type" => "arc",
                "unit"          => "m"
            ]
        ];
        $sort === 'new_user' && $lbsSort = [
            'created_at' => ['order' => 'DESC']
        ];
        $sort === 'charm_first' && $lbsSort = [//魅力优先
            'followed_count' => ['order' => 'DESC']
        ];
        if ($cityId && version_compare($version, '2.0.0', '>=') && ($sort === 'except_inactive')) {
            $lbsSort = [
                'active_at' => ['order' => 'DESC']
            ];
        }
        $params = [
            'index' => 'user_location',
            'body'  => [
                'sort'  => [$lbsSort],
                'from'  => $from,
                'size'  => $size,
                'query' => [
                    'bool' => [
                        'filter'   => array_merge($distance, $match),
                        'must_not' => [
                            'terms' => [
                                '_id' => pocket()->user->getLBSNotUserId(),
                            ]
                        ]
                    ]
                ],
            ]
        ];
        try {
            $response = $this->getClient()->search($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return [];
        }
        if (!isset($response['hits']['hits'])) {
            return [];
        }
        $hits = $response['hits']['hits'];

        return array_column(array_column($hits, '_source'), 'user_id');
    }

    /**
     * 搜索打招呼用户
     *
     * @param       $userId
     * @param  int  $lon
     * @param  int  $lat
     *
     * @return array
     */
    public function searchGreetUsers($userId, $lon = 0, $lat = 0)
    {
        $hasGreetUserIds = pocket()->greet->hasGreetUserIds($userId);
        $breakUserId     = pocket()->blacklist->getBreakBlockUserId();
        $exceptUserIds   = array_merge($hasGreetUserIds, $breakUserId);
        $userIds         = $this->searchGreetUser($userId, $exceptUserIds, $lon, $lat);      //普通用户6个
        $memberIds       = $this->searchGreetMmeberUser($userId, $exceptUserIds, $lon, $lat);//会员3个

        return array_merge($userIds, $memberIds);
    }

    /**
     * 搜索会员
     *
     * @param       $userId
     * @param  int  $lon
     * @param  int  $lat
     * @param       $exceptUserIds
     *
     * @return array
     */
    public function searchGreetMmeberUser($userId, $exceptUserIds, $lon = 0, $lat = 0)
    {
        $userIds     = [];
        $page        = 0;
        $size        = 100;
        $currentStep = 0;
        $step        = [
            [
                'start_distance' => '1m',
                'end_distance'   => '50km',
            ],
            [
                'start_distance' => '50km',
                'end_distance'   => '5000km',
            ]
        ];
        $gender      = User::GENDER_MAN;
        do {
            $from     = $page * $size;
            $stepInfo = $step[$currentStep] ?? -1;
            if ($stepInfo == -1) {
                break;
            }

            $pageUserIds = $this->searchGreetUserByPage(
                $from,
                $size,
                $lon,
                $lat,
                $gender,
                $stepInfo['start_distance'],
                $stepInfo['end_distance'],
                0, 0, 1,
                $exceptUserIds
            );
            if (count($pageUserIds) == 0) {
                $page = 0;
                $currentStep++;
            }
            foreach ($pageUserIds as $pageUserId) {
                if (count($userIds) < 3) {
                    if (!in_array($pageUserId, $userIds, true)) {
                        $userIds[] = $pageUserId;
                    }
                } else {
                    break;
                }
            }
            $page++;
        } while (count($userIds) < 3);

        return $userIds;
    }

    /**
     * 搜索用户
     *
     * @param       $userId
     * @param       $exceptUserIds
     * @param  int  $lon
     * @param  int  $lat
     *
     * @return array
     */
    public function searchGreetUser($userId, $exceptUserIds, $lon = 0, $lat = 0) : array
    {
        $userIds     = [];
        $page        = 0;
        $size        = 100;
        $currentStep = 0;
        $step        = [
            [
                'start_distance' => '1m',
                'end_distance'   => '50km',
                'great_count'    => 3,
            ],
            [
                'start_distance' => '1m',
                'end_distance'   => '50km',
                'great_count'    => 8,
            ],
            [
                'start_distance' => '50km',
                'end_distance'   => '5000km',
                'great_count'    => 3,
            ],
            [
                'start_distance' => '50km',
                'end_distance'   => '5000km',
                'great_count'    => 8,
            ],
        ];

        $gender = User::GENDER_MAN;
        do {
            $from     = $page * $size;
            $stepInfo = $step[$currentStep] ?? -1;
            if ($stepInfo == -1) {
                break;
            }
            $pageUserIds = $this->searchGreetUserByPage(
                $from,
                $size,
                $lon,
                $lat,
                $gender,
                $stepInfo['start_distance'],
                $stepInfo['end_distance'],
                $stepInfo['great_count'],
                0, 0,
                $exceptUserIds
            );
            if (count($pageUserIds) == 0) {
                $page = 0;
                $currentStep++;
            }
            foreach ($pageUserIds as $pageUserId) {
                if (count($userIds) < 6) {
                    if (!in_array($pageUserId, $userIds, true)) {
                        $userIds[] = $pageUserId;
                    }
                } else {
                    break;
                }
            }
            $page++;
        } while (count($userIds) < 6);

        return $userIds;
    }

    /**
     * 从es中搜索推荐的用户
     *
     * @param  int     $from
     * @param  int     $size
     * @param  int     $lon
     * @param  int     $lat
     * @param  int     $gender
     * @param  string  $km
     * @param  int     $greatCount
     * @param  int     $hide
     * @param  int     $isMember
     * @param  array   $exceptUserIds
     *
     * @return array
     */
    public function searchGreetUserByPage(
        $from = 0,
        $size = 10,
        $lon = 0,
        $lat = 0,
        $gender = 1,
        $startDistance = '1m',
        $endDistance = '50km',
        $greatCount = 0,
        $hide = 0,
        $isMember = -1,
        $exceptUserIds = [],
        $sort = 'active'
    ) : array {
        if ($lat == 0 && $lon == 0) {
            $lat = 39.904989;
            $lon = 116.405285;
        }
        $match   = [];
        $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['destroy_at' => 0]]
                ]
            ]
        ];
        $gender && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['gender' => $gender]]
                ]
            ]
        ];
        $greatCount && $match[] = [
            'range' => [
                'greet_count_two_days' => ['lte' => $greatCount]
            ]
        ];
        $isMember != -1 && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['is_member' => $isMember]]
                ]
            ]
        ];

        $match[]    = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['hide' => $hide]]
                ]
            ]
        ];
        $distance[] = [
            'geo_distance' => [
                "distance" => $endDistance,
                "location" => [
                    "lat" => (float)$lat,
                    "lon" => (float)$lon,
                ],
            ],
        ];

        $sort === 'distance' && $lbsSort = [
            '_geo_distance' => [
                "location"      => [(float)$lon, (float)$lat],
                "order"         => "asc",
                "mode"          => "min",
                "distance_type" => "arc",
                "unit"          => "m"
            ]
        ];
        $sort === 'active' && $lbsSort = [
            'active_at' => ['order' => 'DESC']
        ];
        $params = [
            'index' => 'user_location',
            'body'  => [
                'sort'  => [$lbsSort],
                'from'  => $from,
                'size'  => $size,
                'query' => [
                    'bool' => [
                        'filter'   => array_merge($distance, $match),
                        'must_not' => [
                            [
                                'terms' => [
                                    '_id' => array_merge($exceptUserIds, pocket()->user->getLBSNotUserId()),
                                ]
                            ],
                            [
                                'geo_distance' => [
                                    "distance" => $startDistance,
                                    "location" => [
                                        "lat" => (float)$lat,
                                        "lon" => (float)$lon,
                                    ],
                                ],
                            ]
                        ]
                    ]
                ],
            ]
        ];
        try {
            $response = $this->getClient()->search($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return [];
        }
        if (!isset($response['hits']['hits'])) {
            return [];
        }
        $hits = $response['hits']['hits'];

        return array_column(array_column($hits, '_source'), 'user_id');
    }

    /**
     * 搜索某个人附近的人
     *
     * @param       $userId
     * @param       $count
     * @param       $exceptUserIds
     * @param  int  $gender
     *
     * @return array
     * @throws \Exception
     */
    public function searchVisited($userId, $count, $exceptUserIds = [], $gender = User::GENDER_WOMEN)
    {
        $userIds = [];
        $user    = rep()->userDetail->m()->where('user_id', $userId)->first();
        $user && $userIds = $this->searchGreetUserByPage(
            0,
            $count,
            $user->lng,
            $user->lat,
            $gender,
            '1m',
            '5000km',
            0,
            0,
            -1,
            $exceptUserIds,
            'distance'
        );

        return $userIds;
    }

    /**
     * 获取新入用户的数量
     * @return int
     */
    public function getNewUserCount($userId, $gender = 0, $time = 0) : int
    {
        $userDetail = rep()->userDetail->m()->select(['lat', 'lng'])->where('user_id', $userId)->first();
        $time       = $time == 0 ? time() : $time;
        $gender && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['gender' => $gender]]
                ]
            ]
        ];
        if ($gender == User::GENDER_MAN) {
            $time && $match[] = [
                'range' => [
                    'created_at' => ['gte' => $time]
                ]
            ];
        }
        if ($gender == User::GENDER_WOMEN) {
            $time && $match[] = [
                'range' => [
                    'charm_girl_done_at' => ['gte' => $time]
                ]
            ];
        }

        $userDetail && $distance[] = [
            'geo_distance' => [
                "distance" => "100km",
                "location" => [
                    "lat" => (float)$userDetail->lat,
                    "lon" => (float)$userDetail->lng,
                ],
            ],
        ];

        $lbsSort = [
            'created_at' => ['order' => 'DESC']
        ];
        $params  = [
            'index' => 'user_location',
            'body'  => [
                'sort'  => [$lbsSort],
                'from'  => 0,
                'size'  => 1,
                'query' => [
                    'bool' => [
                        'filter' => array_merge($distance, $match),
                    ]
                ],
            ]
        ];
        try {
            $response = $this->getClient()->search($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return 0;
        }
        if (!isset($response['hits']['total'])) {
            return 0;
        }

        return $response['hits']['total']['value'] ?? 0;
    }

    /**
     * 获得用户的信息
     *
     * @param $userId
     *
     * @return array
     */
    public function getUserByUserId($userId) : array
    {
        $params = [
            'index' => 'user_location',
            'id'    => $userId
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
     * 更新用户位置信息到es
     *
     * @param  int  $userId
     * @param  int  $lng
     * @param  int  $lat
     *
     * @return ResultReturn
     */
    public function updateOrPostUserLocation(int $userId, $lng = 0, $lat = 0) : ResultReturn
    {
        $user = rep()->user->getById($userId);
        if (!$user) {
            return ResultReturn::failed(trans('messages.user_not_exist'), ['code' => 1]);
        }
        $userDetail = rep()->userDetail->m()
            ->select(['user_id', "followed_count"])
            ->where('user_id', $user->id)
            ->first();
        $esUser     = $this->getUserByUserId($userId);
        if ($esUser && isset($esUser['location']['lat']) && $esUser['location']['lat'] != 0) {
            $lng = $lng == 0 ? $esUser['location']['lon'] : $lng;
            $lat = $lat == 0 ? $esUser['location']['lat'] : $lat;
        }

        $uploadLocation = User::MONGO_LOC_IS_UPLOAD;
        if ($lng == 0 && $lat == 0) {
            $uploadLocation = User::MONGO_LOC_NOT_UPLOAD;
        }

        $cityName = pocket()->userDetail->getCityByLoc($lng, $lat);
        $city     = rep()->area->m()->select(['id', 'level', 'name', 'pid'])
            ->where('name', $cityName)
            ->where('level', 2)
            ->first();
        $cityId   = $provinceId = 0;
        if ($city) {
            $cityId     = $city->id;
            $provinceId = $city->pid;
        }

        if (!$esUser) {
            $isShow = $user->appname == 'bojinquan' ? User::COLD_START_HIDE : User::SHOW;
            $params = [
                'index' => 'user_location',
                'id'    => $user->id,
                'body'  => [
                    'user_id'              => $user->id,
                    'charm_girl'           => (int)pocket()->user->hasRole($user, User::ROLE_CHARM_GIRL),
                    'gender'               => $user->gender,
                    'location'             => [
                        'lat' => (float)$lat,
                        'lon' => (float)$lng,
                    ],
                    'upload_location'      => $uploadLocation,
                    'city_id'              => $cityId,
                    'province_id'          => $provinceId,
                    'hide'                 => $isShow,
                    'greet_count_two_days' => 0,
                    'is_member'            => 0,
                    'followed_count'       => 0,
                    'destroy_at'           => 0,
                    'created_at'           => time(),
                    'active_at'            => $user->active_at
                ]
            ];

            try {
                $response = $this->getClient()->index($params);
            } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
                return ResultReturn::failed($e->getMessage(), ['code' => 3]);
            } catch (\Exception $e) {
                return ResultReturn::failed($e->getMessage(), ['code' => 2]);
            }
            rep()->userDetail->m()->where('user_id', $userId)->update([
                'lat' => (float)$lat,
                'lng' => (float)$lng,
            ]);

            return ResultReturn::success($response);
        }
        $params = [
            'index' => 'user_location',
            'id'    => $user->id,
            'body'  => [
                'doc' => [
                    'user_id'         => $user->id,
                    'charm_girl'      => (int)pocket()->user->hasRole($user, User::ROLE_CHARM_GIRL),
                    'gender'          => $user->gender,
                    'location'        => [
                        'lat' => (float)$lat,
                        'lon' => (float)$lng,
                    ],
                    'upload_location' => $uploadLocation,
                    'city_id'         => $cityId,
                    'province_id'     => $provinceId,
                    'hide'            => $user->hide,
                    'is_member'       => (int)$user->isMember(),
                    'followed_count'  => $userDetail->followed_count ?? 0,
                    'destroy_at'      => $user->destroy_at,
                ]

            ]
        ];

        try {
            $response = $this->getClient()->update($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return ResultReturn::failed($e->getMessage(), ['code' => 3]);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage(), ['code' => 2]);
        }

        rep()->userDetail->m()->where('user_id', $userId)->update([
            'lat' => (float)$lat,
            'lng' => (float)$lng,
        ]);
        $job = (new UpdateUserInfoToMongoJob($user->id))->onQueue('update_user_info_to_mongo');
        dispatch($job);

        return ResultReturn::success($response);
    }

    /**
     * 更新用户活跃时间到es
     *
     * @param  int    $userId
     * @param  array  $updateField
     *
     * @return ResultReturn
     */
    public function updateUserFieldToEs(int $userId, array $updateField)
    {
        //        $user = DB::table('user')->select('id')->where('id', $userId)->first();
        //        if (!$user) {
        //            return ResultReturn::failed('用户不存在', ['code' => 1]);
        //        }
        //        $esUser = $this->getUserByUserId($userId);
        //        if (!$esUser) {
        //            return ResultReturn::failed('es用户不存在', ['code' => 2]);
        //        }
        $params = [
            'index' => 'user_location',
            'id'    => $userId,
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
     * 批量设置用户位置信息
     *
     * @param $userIds array  用户ids
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function batchUpdateOrPostUserLocationFromMongo(array $userIds)
    {
        $mongoUsers = mongodb('user')->whereIn('_id', $userIds)->get();
        if (!$mongoUsers) {
            return ResultReturn::failed(trans('messages.user_not_exist'), ['code' => 1]);
        }
        $params = [];
        foreach ($mongoUsers as $user) {
            $params['body'][] = [
                'index' => [
                    '_index' => 'user_location',
                    '_id'    => $user['_id']
                ]
            ];
            $params['body'][] = [
                'user_id'         => $user['_id'],
                'charm_girl'      => $user['charm_girl'] ?? 0,
                'gender'          => $user['gender'] ?? 0,
                'location'        => [
                    'lat' => $user['location'][1] ?? 0,
                    'lon' => $user['location'][0] ?? 0,
                ],
                'upload_location' => $user['upload_location'] ?? 0,
                'active_at'       => $user['active_at'] ?? 0,
            ];
        }

        try {
            $response = $this->getClient()->bulk($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return ResultReturn::failed($e->getMessage(), ['code' => 3]);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage(), ['code' => 2]);
        }

        return ResultReturn::success($response);
    }

    /**
     * 批量设置用户城市信息
     *
     * @param  array  $cityInfos
     *
     * @return ResultReturn
     */
    public function batchUpdateEsCity(array $cityInfos)
    {
        $params = [];
        foreach ($cityInfos as $cityInfo) {
            if (!isset($cityInfo['user_id']) || $cityInfo['user_id'] === 0) {
                continue;
            }
            if (!isset($cityInfo['city_id']) || $cityInfo['city_id'] === 0) {
                continue;
            }
            if (!isset($cityInfo['province_id']) || $cityInfo['province_id'] === 0) {
                continue;
            }

            $params['body'][] = [
                'update' => [
                    '_index' => 'user_location',
                    '_id'    => $cityInfo['user_id']
                ]
            ];

            $params['body'][] = [
                'doc_as_upsert' => 'true',
                'doc'           => [
                    'user_id'     => $cityInfo['user_id'],
                    'city_id'     => $cityInfo['city_id'],
                    'province_id' => $cityInfo['province_id'],
                    'hide'        => $cityInfo['hide'],
                ]
            ];
        }
        if (empty($params)) {
            return ResultReturn::failed(trans('messages.not_found_userinfo'), ['code' => 4]);
        }

        try {
            $response = $this->getClient()->bulk($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return ResultReturn::failed($e->getMessage(), ['code' => 3]);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage(), ['code' => 2]);
        }

        return ResultReturn::success($response);
    }

    /**
     * 删除用户信息
     *
     * @param $userId  int 用户id
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function deleteUserInfoByUserId($userId)
    {
        $params = [
            'index' => 'user',
            'id'    => $userId
        ];

        try {
            $response = $this->getClient()->delete($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return ResultReturn::success($params);
        }

        return ResultReturn::success($response);
    }

    /**
     * 创建用户信息
     *
     * @param $userId int   用户id
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public
    function postUserInfoByUserId(
        $userId
    ) {
        $user = rep()->user->getById($userId, ['id', 'nickname', 'number', 'gender'])->data;
        if (!$user) {
            return ResultReturn::failed(trans('messages.user_not_exist'));
        }
        $params = [
            'index' => 'user',
            'id'    => $user->id,
            'body'  => [
                'id'         => $userId,
                'nickname'   => $user->nickname,
                'number'     => $user->number,
                'gender'     => $user->gender,
                'blocked_at' => $user->blocked_at,
                'deleted_at' => $user->deleted_at
            ]
        ];

        try {
            $response = $this->getClient()->create($params);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }

        return ResultReturn::success($response);
    }

    /**
     * 从es中获得在线优先的人列表
     *
     * @param  string  $page
     * @param  int     $size
     * @param  int     $lon
     * @param  int     $lat
     * @param  int     $charmGril
     * @param  int     $gender
     * @param  int     $uploadLocation
     * @param  string  $sort
     * @param  int     $cityId
     * @param  int     $hide
     * @param  int     $isMember
     * @param  string  $version
     * @param  int     $bucket
     *
     * @return array
     */
    public function getLbsOnlineUsers(
        $page = '',
        $size = 10,
        $lon = 0,
        $lat = 0,
        $charmGril = 0,
        $gender = 0,
        $uploadLocation = 0,
        $sort = 'common',
        $cityId = 0,
        $hide = 0,
        $isMember = 0,
        $version = '1.0.0',
        array $excludeUsersId = []
    ) {
        $pageArr = ['bucket' => 1, 'from' => 0];
        if (strpos($page, '-') !== false) {
            $pageExArr = explode('-', $page);
            $pageArr   = [
                'bucket' => $pageExArr[0],
                'from'   => $pageExArr[1],
            ];
        }
        $buckets      = [
            1 => [
                'start_distance' => '1m',
                'end_distance'   => '50km',
                'start_active'   => time() - (10 * 60),
                'stop_active'    => time(),
                'from'           => 0,
                'sort_arr'       => [
                    'active_at' => ['order' => 'DESC']
                ],
            ],
            2 => [
                'start_distance' => '1m',
                'end_distance'   => '20km',
                'start_active'   => time() - (60 * 60),
                'stop_active'    => time() - (10 * 60),
                'from'           => 0,
                'sort_arr'       => [
                    'active_at' => ['order' => 'DESC']
                ],
            ],
            3 => [
                'start_distance' => '20km',
                'end_distance'   => '50km',
                'start_active'   => time() - (60 * 60),
                'stop_active'    => time() - (10 * 60),
                'from'           => 0,
                'sort_arr'       => [
                    'active_at' => ['order' => 'DESC']
                ],
            ],
            4 => [
                'start_distance' => '1m',
                'end_distance'   => '50km',
                'start_active'   => 0,
                'stop_active'    => time() - (60 * 60),
                'from'           => 0,
                'sort_arr'       => [
                    'active_at' => ['order' => 'DESC']
                ],
            ],
            5 => [
                'start_distance' => '50km',
                'end_distance'   => '500000km',
                'start_active'   => 0,
                'stop_active'    => time(),
                'from'           => 0,
                'sort_arr'       => [
                    '_geo_distance' => [
                        "location"      => [(float)$lon, (float)$lat],
                        "order"         => "asc",
                        "mode"          => "min",
                        "distance_type" => "arc",
                        "unit"          => "m"
                    ]
                ],
            ]
        ];
        $finalBuckets = [];
        foreach ($buckets as $key => $bucket) {
            if ($key < $pageArr['bucket']) {
                continue;
            }
            if ($key == $pageArr['bucket']) {
                $bucket['from'] = $pageArr['from'];
            }
            $finalBuckets[$key] = $bucket;
        }
        $finalUsersId = [];
        $pageNum      = $selfSize = $size;
        $lastFrom     = $pageArr['from'];
        foreach ($finalBuckets as $key => $bucket) {
            $userIds       = pocket()->esUser->getUsersIdByDistanceAndActive(
                (int)$bucket['from'],
                $selfSize,
                $lon,
                $lat,
                (int)($gender === User::GENDER_WOMEN),
                $gender,
                User::MONGO_LOC_IS_UPLOAD,
                $sort,
                $cityId,
                User::SHOW,
                $isMember,
                $version,
                $bucket['start_distance'],
                $bucket['end_distance'],
                $bucket['start_active'],
                $bucket['stop_active'],
                $excludeUsersId,
                $bucket['sort_arr']
            );
            $currentUserId = array_slice($userIds, 0, $pageNum - count($finalUsersId));
            $finalUsersId  = array_merge($finalUsersId, $currentUserId);
            $lastBucket    = $key;
            if ($lastBucket == $pageArr['bucket']) {
                $lastFrom += count($currentUserId);
            } else {
                $lastFrom = count($currentUserId);
            }
            if (count($finalUsersId) >= $pageNum) {
                break;
            }
            $selfSize = $pageNum - count($finalUsersId);
        }

        return [$finalUsersId, $lastBucket . '-' . $lastFrom];
    }

    public function testEsExceptIds(
        $ids = [],
        $from = 0,
        $size = 10,
        $lon = 0,
        $lat = 0,
        $charmGril = 0,
        $gender = 0,
        $uploadLocation = 0,
        $sort = 'common',
        $cityId = 0,
        $hide = 0,
        $isMember = 0,
        $version = '1.0.0'
    ) : array {
        if ($lat == 0 && $lon == 0) {
            $lat = 39.904989;
            $lon = 116.405285;
        }
        $match   = [];
        $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['destroy_at' => 0]]
                ]
            ]
        ];
        $gender && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['gender' => $gender]]
                ]
            ]
        ];
        $charmGril && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['charm_girl' => $charmGril]]
                ]
            ]
        ];
        $uploadLocation && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['upload_location' => $uploadLocation]]
                ]
            ]
        ];
        $sort === 'except_inactive' && $match[] = [
            'range' => [
                'active_at' => ['gte' => time() - config('custom.sort_active_time')]
            ]
        ];
        $cityId && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['city_id' => $cityId]]
                ]
            ]
        ];
        $match[]    = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['hide' => $hide]]
                ]
            ]
        ];
        $distance[] = [
            'geo_distance' => [
                "distance" => $cityId ? "5000km" : "100km",
                "location" => [
                    "lat" => (float)$lat,
                    "lon" => (float)$lon,
                ],
            ],
        ];
        $isMember   = (int)$isMember;
        $isMember === 1 && $match[] = [
            'bool' => [
                'must' => [
                    ['match_phrase' => ['is_member' => $isMember]]
                ]
            ]
        ];
        $lbsSort = [
            '_geo_distance' => [
                "location"      => [(float)$lon, (float)$lat],
                "order"         => "asc",
                "mode"          => "min",
                "distance_type" => "arc",
                "unit"          => "m"
            ]
        ];
        $sort === 'new_user' && $lbsSort = [
            'created_at' => ['order' => 'DESC']
        ];
        $sort === 'charm_first' && $lbsSort = [//魅力优先
            'followed_count' => ['order' => 'DESC']
        ];
        if ($cityId && version_compare($version, '2.0.0', '>=') && ($sort === 'except_inactive')) {
            $lbsSort = [
                'active_at' => ['order' => 'DESC']
            ];
        }
        $params = [
            'index' => 'user_location',
            'body'  => [
                'sort'  => [$lbsSort],
                'from'  => $from,
                'size'  => $size,
                'query' => [
                    'bool' => [
                        'filter'   => array_merge($distance, $match),
                        'must_not' => [
                            'terms' => [
                                '_id' => $ids,
                            ]
                        ]
                    ]
                ],
            ]
        ];
        try {
            $response = $this->getClient()->search($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return [];
        }
        if (!isset($response['hits']['hits'])) {
            return [];
        }
        $hits = $response['hits']['hits'];

        return array_column(array_column($hits, '_source'), 'user_id');
    }

    /**
     * 获取一个打招呼的假女用户ID
     *
     * @param $userId
     *
     * @return ResultReturn
     */
    public function getFakeGreetUser($userId) : ResultReturn
    {
        $userDetail = rep()->userDetail->getByUserId($userId);
        // 铂金圈V1.2.2
        // 20KM内正在活跃、50KM内正在活跃、100KM内正在活跃，如果还找不到就不找了
        $buckets = [
            1 => [
                'start_distance' => '1m',
                'end_distance'   => '20km',
                'start_active'   => time() - (10 * 60),
                'stop_active'    => time(),
                'from'           => 0
            ],
            2 => [
                'start_distance' => '20km',
                'end_distance'   => '50km',
                'start_active'   => time() - (10 * 60),
                'stop_active'    => time(),
                'from'           => 0
            ],
            3 => [
                'start_distance' => '50km',
                'end_distance'   => '100km',
                'start_active'   => time() - (10 * 60),
                'stop_active'    => time(),
                'from'           => 0
            ]
        ];
        $data    = [];
        // 已经发过的用户不再发送
        $redisKeyUsed = sprintf(config('redis_keys.has_used_users.key'), $userId);
        $usedIds      = redis()->client()->sMembers($redisKeyUsed);
        if (!$usedIds) {
            $usedIds = [];
        }
        foreach ($buckets as $item) {
            $result = pocket()->esUser->getUsersIdByDistanceAndActive(
                (int)$item['from'],
                1000,
                $userDetail->lng,
                $userDetail->lat,
                1,
                0,
                User::MONGO_LOC_IS_UPLOAD,
                0,
                0,
                User::SHOW,
                0,
                '1.0.0',
                $item['start_distance'],
                $item['end_distance'],
                $item['start_active'],
                $item['stop_active'],
                []
            );
            if (count($result) > 0) {
                $data = array_diff($result, $usedIds);
                if (count($data) > 0) {
                    break;
                }
            }

        }
        if (count($data) > 0) {
            $girlId = $data[array_rand($data)];

            return ResultReturn::success(['girl_id' => $girlId]);
        } else {
            return ResultReturn::failed('用户已不存在');
        }
    }
}

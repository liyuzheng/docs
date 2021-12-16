<?php


namespace App\Pockets;

use App\Exceptions\ServiceException;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Foundation\Modules\ResultReturn\ResultReturnStructure;

class EsImChatPocket extends EsPocket
{
    public function __construct()
    {
        $this->indexName = $this->getIndexName();
    }

    /**
     * 获得index名称
     *
     * @return string
     */
    public function getIndexName() : string
    {
        return 'chat';
    }

    /**
     * 创建Index
     *
     * @param  bool  $force
     *
     * @return ResultReturnStructure
     */
    public function postIndex($force = false)
    {
        return $this->postIndexBy($this->getIndexName(), $this->getSettingsConfig(), $force);
    }

    /**
     * 设置 mapping
     * @return array
     */
    public function getMappingsConfig()
    {
        $mapping = [
            '_source'    => [
                'enabled' => true
            ],
            'properties' => [
                'scene'      => [
                    'type'  => 'long',
                    'index' => true
                ],
                'scene_id'   => [
                    'type'  => 'long',
                    'index' => true
                ],
                'type'       => [
                    'type'  => 'long',
                    'index' => true
                ],
                'send_id'    => [
                    'type'  => 'long',
                    'index' => true
                ],
                'receive_id' => [
                    'type'  => 'long',
                    'index' => true
                ],
                'group'      => [
                    'type'  => 'text',
                    'index' => true
                ],
                'content'    => [
                    'type'            => 'text',
                    'analyzer'        => 'content_analyzer',
                    'search_analyzer' => 'content_analyzer',
                    'index'           => true,
                ],
                'send_at'    => [
                    'type'  => 'long',
                    'index' => true,
                ],
                'body'       => [
                    'type' => 'text',
                ],
                'status'     => [
                    'type'  => 'long',
                    'index' => true,
                ],
                'created_at' => [
                    'type'  => 'long',
                    'index' => true,
                ]
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
            'analysis'           => [
                'analyzer'  => [
                    'content_analyzer' => [
                        'tokenizer' => 'content_tokenizer'
                    ]
                ],
                'tokenizer' => [
                    'content_tokenizer' => [
                        'type'        => 'ngram',
                        'min_gram'    => 1,
                        'max_gram'    => 2,
                        'token_chars' => ['letter', 'digit']
                    ]
                ]
            ]
        ];
    }

    /**
     * 更新 mapping
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
     * 获得 mapping
     *
     * @return array
     */
    public function getMappings()
    {
        $params = ['index' => $this->getIndexName()];

        return $this->getClient()->indices()->getMapping($params);
    }

    /**
     * 获得 settings
     *
     * @return array
     */
    public function getSettings()
    {
        $params = ['index' => $this->getIndexName()];

        return $this->getClient()->indices()->getSettings($params);
    }

    /**
     * 查询用户聊天
     *
     * @param  int  $sendId     发送者ID
     * @param  int  $receiveId  接收者ID
     * @param  int  $startTime  发送开始时间
     * @param  int  $limit      获得多少条
     * @param  int  $page       跳过多少条
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function getPaginateImChatByEachUser(
        int $sendId,
        int $receiveId,
        int $startTime,
        $limit = 10,
        $page = 1
    ) {
        $params = [
            'index' => $this->getIndexName(),
            'body'  => [
                'from'  => ($page - 1) * $limit,
                'size'  => $limit,
                'sort'  => [
                    'send_at' => 'asc'
                ],
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'match_phrase' => ['send_id' => ['query' => $sendId]]
                            ],
                            [
                                'match_phrase' => ['receive_id' => ['query' => $receiveId]]
                            ],
                            [
                                'range' => ['send_at' => ['from' => $startTime]]
                            ]
                        ]
                    ],
                ]
            ]
        ];
        try {
            $response = $this->getClient()->search($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $exception) {
            return ResultReturn::failed($exception->getMessage());
        } catch (\Exception $exception) {
            return ResultReturn::failed($exception->getMessage());

        }
        if (!isset($response['hits']['hits'])) {
            return ResultReturn::failed(trans('messages.no_data'));
        }
        $hits = $response['hits']['hits'];
        if (!count($hits)) {
            return ResultReturn::failed(trans('messages.no_data'));
        }
        $body = array_column($hits, '_source');

        return ResultReturn::success([
            'current_page' => $page,
            'next_page'    => ++$page,
            'limit'        => $limit,
            'data'         => $body
        ]);
    }

    /**
     * 从es中模糊搜索用户id
     *
     * @param  string  $keyword  搜索关键字
     * @param  int     $from     跳过行数
     * @param  int     $size     查询数量
     *                           先根据number精确搜索,再根据nickname模糊搜索
     *                           select id from chat WHERE content like "%1%" limit 10
     *
     * @return array
     */
    public function getImChatByContent(string $keyword, $from = 0, $size = 10) : array
    {
        $params = [
            'index' => $this->getIndexName(),
            'body'  => [
                'from'  => $from,
                'size'  => $size,
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'match_phrase' => ['content' => ['query' => $keyword]]
                            ]
                        ]
                    ],
                ]
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
        $hits    = $response['hits']['hits'];
        $usersId = array_column(array_column($hits, '_source'), 'id');

        return $usersId;
    }

    /**
     * 获得用户的信息
     *
     * @param $roomId
     *
     * @return array
     */
    public function getSourceDataByRoomId($roomId)
    {
        $params = [
            'index' => $this->getIndexName(),
            'id'    => $roomId
        ];
        try {
            $content = $this->getClient()->getSource($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            return [];
        }

        return $content;
    }

    /**
     * 批量导入从mongodb导入数据
     *
     * @param $roomId   房间id
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function postBulkUserChatInfoFromMongo(array $args)
    {

        foreach ($args as $arg) {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->getIndexName()
                ],
            ];
            $params['body'][] = [
                'scene'      => 100,
                'scene_id'   => 0,
                'send_id'    => (int)$arg['send_id'],
                'receive_id' => (int)$arg['receive_id'],
                'group'      => $arg['group'],
                'content'    => $arg['content'],
                'send_at'    => (int)$arg['send_at'],
                'body'       => json_encode($arg['body']),
                'created_at' => (int)$arg['created_at']
            ];
        }
        try {
            $response = $this->getClient()->bulk($params);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }

        return ResultReturn::success($response);
    }

    /**
     * 索引一条聊天数据
     *
     * @param  array  $arg
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function postUserChatInfo(array $arg)
    {
        $params = [
            'index' => $this->getIndexName(),
            'id'    => pocket()->util->getSnowflakeId(),
            'body'  => [
                'scene'      => (int)$arg['scene'],
                'scene_id'   => (int)$arg['scene_id'],
                'send_id'    => (int)$arg['send_id'],
                'receive_id' => (int)$arg['receive_id'],
                'group'      => $arg['group'],
                'content'    => $arg['content'],
                'type'       => $arg['type'],
                'send_at'    => (int)$arg['send_at'],
                'body'       => json_encode($arg['body']),
                'created_at' => (int)$arg['created_at']
            ]
        ];
        try {
            $response = $this->getClient()->index($params);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }

        return ResultReturn::success($response);
    }

    /**
     * 获取一条聊天数据
     *
     * @param  mixed  $id
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function getUserChatInfo($id)
    {
        $params = [
            'index' => $this->getIndexName(),
            'id'    => $id,
        ];
        try {
            $response = $this->getClient()->get($params);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }

        return ResultReturn::success($response);
    }

    /**
     * 搜索用户聊天信息
     *
     * @param  int     $sendId     发送者ID (和$receiveId二选一或者都填)
     * @param  int     $receiveId  接收者ID
     * @param  int     $startTime  发送开始时间
     * @param  int     $endTime    发送结束时间
     * @param  string  $keyword    搜索的关键词
     * @param  int     $limit      获得多少条
     * @param  int     $page       跳过多少条
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function searchImChat(
        int $sendId = 0,
        int $receiveId = 0,
        int $startTime = 0,
        int $endTime = 0,
        string $keyword = '',
        array $fields = [],
        int $limit = 10,
        int $page = 1
    ) {
        $must = [];
        if (($sendId > 0 && $receiveId == 0)) {
            $should = [
                'bool' => [
                    'should' => [
                        ['match_phrase' => ['send_id' => ['query' => $sendId]]],
                        ['match_phrase' => ['receive_id' => ['query' => $sendId]]],
                    ]
                ]
            ];
            $must[] = $should;
        }
        if ($sendId == 0 && $receiveId > 0) {
            $should = [
                'bool' => [
                    'should' => [
                        ['match_phrase' => ['send_id' => ['query' => $receiveId]]],
                        ['match_phrase' => ['receive_id' => ['query' => $receiveId]]]
                    ]
                ]
            ];
            $must[] = $should;
        }
        if ($receiveId > 0 && $sendId > 0) {
            $send    = [
                'bool' => [
                    'should' => [
                        ['match_phrase' => ['send_id' => ['query' => $sendId]]],
                        ['match_phrase' => ['receive_id' => ['query' => $sendId]]],
                    ]
                ]
            ];
            $receive = [
                'bool' => [
                    'should' => [
                        ['match_phrase' => ['send_id' => ['query' => $receiveId]]],
                        ['match_phrase' => ['receive_id' => ['query' => $receiveId]]],
                    ]
                ]
            ];
            array_push($must, $send, $receive);
        }
        if ($keyword) {
            $queryKeyword = [
                'match_phrase' => ['content' => ['query' => $keyword]]
            ];
            $must[]       = $queryKeyword;
        }
        if ($startTime >= 0) {
            $queryStartTime = [
                'range' => ['send_at' => ['gte' => $startTime]]
            ];
            $must[]         = $queryStartTime;
        }
        if ($endTime > 0) {
            $queryEndTime = [
                'range' => ['send_at' => ['lte' => $endTime]]
            ];
            $must[]       = $queryEndTime;
        }
        $query  = [
            'bool' => [
                'must' => $must,
            ],
        ];
        $params = [
            'index'   => $this->getIndexName(),
            "_source" => $fields,
            'body'    => [
                'from'  => ($page - 1) * $limit,
                'size'  => $limit,
                'sort'  => [
                    'send_at' => 'asc'
                ],
                'query' => $query
            ]
        ];
        try {
            $response = $this->getClient()->search($params);
        } catch
        (\Elasticsearch\Common\Exceptions\Missing404Exception $exception) {
            return ResultReturn::failed($exception->getMessage());
        } catch (\Exception $exception) {
            return ResultReturn::failed($exception->getMessage());
        }
        if (!isset($response['hits']['hits'])) {
            return ResultReturn::failed(trans('messages.no_data'));
        }
        $hits = $response['hits']['hits'];
        if (!count($hits)) {
            return ResultReturn::failed(trans('messages.no_data'));
        }
        $body = array_column($hits, '_source');

        return ResultReturn::success([
            'current_page' => $page,
            'next_page'    => ++$page,
            'limit'        => $limit,
            'data'         => $body
        ]);
    }

    /**
     * 搜索用户聊天信息
     *
     * @param  int     $sendId     发送者ID (和$receiveId二选一或者都填)
     * @param  int     $receiveId  接收者ID
     * @param  int     $startTime  发送开始时间
     * @param  int     $endTime    发送结束时间
     * @param  string  $keyword    搜索的关键词
     * @param  int     $limit      获得多少条
     * @param  int     $page       跳过多少条
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function searchImChatSend(
        int $sendId = 0,
        int $receiveId = 0,
        int $startTime = 0,
        int $endTime = 0,
        string $keyword = '',
        array $fields = [],
        int $limit = 10,
        int $page = 1
    ) {
        $must = [];
        if (($sendId > 0 && $receiveId == 0)) {
            $should = [
                'bool' => [
                    'should' => [
                        ['match_phrase' => ['send_id' => ['query' => $sendId]]],
                    ]
                ]
            ];
            $must[] = $should;
        }
        if ($startTime >= 0) {
            $queryStartTime = [
                'range' => ['send_at' => ['gte' => $startTime]]
            ];
            $must[]         = $queryStartTime;
        }
        if ($endTime > 0) {
            $queryEndTime = [
                'range' => ['send_at' => ['lte' => $endTime]]
            ];
            $must[]       = $queryEndTime;
        }
        $query  = [
            'bool' => [
                'must' => $must,
            ],
        ];
        $params = [
            'index'   => $this->getIndexName(),
            "_source" => $fields,
            'body'    => [
                'from'  => ($page - 1) * $limit,
                'size'  => $limit,
                'sort'  => [
                    'send_at' => 'asc'
                ],
                'query' => $query
            ]
        ];
        try {
            $response = $this->getClient()->search($params);
        } catch
        (\Elasticsearch\Common\Exceptions\Missing404Exception $exception) {
            return ResultReturn::failed($exception->getMessage());
        } catch (\Exception $exception) {
            return ResultReturn::failed($exception->getMessage());
        }
        if (!isset($response['hits']['hits'])) {
            return ResultReturn::failed(trans('messages.no_data'));
        }
        $hits = $response['hits']['hits'];
        if (!count($hits)) {
            return ResultReturn::failed(trans('messages.no_data'));
        }
        $body = array_column($hits, '_source');

        return ResultReturn::success([
            'current_page' => $page,
            'next_page'    => ++$page,
            'limit'        => $limit,
            'data'         => $body
        ]);
    }

    /**
     * 分组查询用户
     *
     * @param  array   $filters       过滤条件
     * @param  array   $ranges
     * @param  string  $groupByField  分组字段
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function getImChatGroupBy(array $filters, array $ranges, string $groupByField)
    {
        if (!in_array($groupByField, ['send_id', 'receive_id'])) {
            return ResultReturn::failed(trans('messages.only_by_field'));
        }
        $must = $query = $should = [];
        foreach ($filters as $filter) {
            $must[] = [
                'match_phrase' => $filter
            ];
        }
        foreach ($ranges as $range) {
            $must[] = [
                'range' => $range
            ];
        }
        $params = [
            'index' => $this->getIndexName(),
            'body'  => [
                'sort'         => [
                    'send_at' => 'desc'
                ],
                'query'        => [
                    'bool' => [
                        'must' => $must
                    ],
                ],
                'aggregations' => [
                    $groupByField => [
                        'terms' => [
                            'field' => $groupByField,
                            'size'  => 500,
                        ],
                    ]
                ]
            ]
        ];
        try {
            $response = $this->getClient()->search($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $exception) {

            return ResultReturn::failed($exception->getMessage());
        } catch (\Exception $exception) {

            return ResultReturn::failed($exception->getMessage());

        }
        if (!isset($response['aggregations'][$groupByField]['buckets'])) {

            return ResultReturn::failed(trans('messages.not_result'));
        }
        $buckets = $response['aggregations'][$groupByField]['buckets'];
        if (!count($buckets)) {

            return ResultReturn::failed(trans('messages.no_data'));
        }
        $body = array_column($buckets, 'key');

        return ResultReturn::success([
            'data' => $body
        ]);
    }

    /**
     * 查询某个日期范围内聊天的图片数量
     *
     * @param  array  $filters
     * @param  array  $ranges
     *
     * @return ResultReturn
     */
    public function searchChatImgCount(array $filters, array $ranges)
    {
        $must = $query = $should = [];
        foreach ($filters as $filter) {
            $must[] = [
                'match_phrase' => $filter
            ];
        }
        foreach ($ranges as $range) {
            $must[] = [
                'range' => $range
            ];
        }
        $params = [
            'index' => $this->getIndexName(),
            'body'  => [
                'sort'  => [
                    'send_at' => 'desc'
                ],
                'query' => [
                    'bool' => [
                        'must' => $must
                    ],
                ],
            ]
        ];
        try {
            $response = $this->getClient()->search($params);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $exception) {
            return ResultReturn::failed($exception->getMessage());
        } catch (\Exception $exception) {
            return ResultReturn::failed($exception->getMessage());
        }

        return ResultReturn::success([
            'data' => $response['hits']['total'],
        ]);
    }

    /**
     * 保存垃圾信息到mongo
     *
     * @param $content
     * @param $sendId
     * @param $receiveId
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function saveSpamMassage($data)
    {
        if ($data['send_id'] == 1 || $data['send_id'] == 2) {
            return ResultReturn::success([]);
        }
        $numberCount        = 2;
        $charCount          = 2;
        $content            = $data['content'];
        $data['expired_at'] = new \MongoDB\BSON\UTCDateTime(new \DateTime(date('Y-m-d H:i:s', time())));
        preg_match_all("/[0-9]{1}/i", $content, $numbers);
        preg_match_all("/[a-zA-Z]{1}/i", $content, $chars);

        if (count($numbers[0]) >= $numberCount || count($chars[0]) >= $charCount) {
            mongodb('message_spam')->insert($data);
        }

        //        if ((count($numbers[0]) >= $numberCount && count($chars[0]) >= $charCount) || count($chars[0]) >= $charCount) {
        //            mongodb('message_spam')->insert($data);
        //        }

        return ResultReturn::success([]);
    }
}

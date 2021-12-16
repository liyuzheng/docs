<?php


namespace App\Pockets;

use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Foundation\Modules\ResultReturn\ResultReturnStructure;

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
     * 更新用户活跃时间到es
     *
     * @param  int    $userId
     * @param  array  $updateField
     *
     * @return ResultReturn
     */
    public function updateUserFieldToEs(int $userId, array $updateField) : ResultReturn
    {
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

}

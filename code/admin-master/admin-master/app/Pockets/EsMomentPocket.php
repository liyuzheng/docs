<?php


namespace App\Pockets;

use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Foundation\Modules\ResultReturn\ResultReturnStructure;
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
            return ResultReturn::failed('动态不存在', ['code' => 1]);
        }
        $esUser = $this->getMomentByMomentId($momentId);
        if (!$esUser) {
            return ResultReturn::failed('es动态不存在', ['code' => 2]);
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
}

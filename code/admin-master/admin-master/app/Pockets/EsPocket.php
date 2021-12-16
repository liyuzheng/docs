<?php

namespace App\Pockets;

use Elasticsearch\ClientBuilder;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Foundation\Modules\ResultReturn\ResultReturnStructure;

class EsPocket extends BasePocket
{
    protected        $indexName;
    protected static $clientInstance;

    const SCENE_CHAT = 100;
    const TYPE_TEXT  = 100;
    const TYPE_IMAGE = 200;
    const TYPE_AUDIO = 300;

    /**
     * @return \Elasticsearch\Client
     */
    protected function getClient()
    {
        if (!self::$clientInstance instanceof ClientBuilder) {
            $esHostArr            = explode(',', config('custom.es.host'));
            $host                 = $esHostArr[array_rand($esHostArr, 1)];
            self::$clientInstance = ClientBuilder::create()->setHosts([$host])->build();
        }

        return self::$clientInstance;
    }

    /**
     * 创建索引
     *
     * @param  string  $indexName      索引名称
     * @param  array   $settingConfig  settingConfig
     * @param  bool    $force          是否强制删除
     *
     * @return ResultReturn|ResultReturnStructure
     */
    protected function postIndexBy(string $indexName, array $settingConfig, $force = false)
    {
        $params = ['index' => $indexName];
        if ($this->getClient()->indices()->exists($params)) {
            if (!$force) {
                return ResultReturn::failed('existing');
            }
            //            $this->deleteIndexBy($indexName);
        }
        $createRes = $this->getClient()->indices()->create(array_merge(
            $params,
            ['body' => ['settings' => $settingConfig]]
        ));

        return ResultReturn::success($createRes);
    }


    /**
     * 删除Index
     *
     * @return ResultReturn|ResultReturnStructure
     */
    public function deleteIndex()
    {
        $params = ['index' => $this->indexName];

        if ($this->getClient()->indices()->exists($params)) {
            $this->getClient()->indices()->delete($params);

            return ResultReturn::success($params);
        }

        return ResultReturn::success($params);
    }
}

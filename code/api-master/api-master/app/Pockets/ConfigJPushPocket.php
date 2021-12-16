<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\ConfigJpush;

class ConfigJPushPocket extends BasePocket
{
    /**
     * 获得要推送的ios极光
     *
     * @return array
     */
    public function getIosJPushArr()
    {
        $jpush      = rep()->configJpush->m()
            ->where('os', ConfigJpush::OS_IOS)
            ->where('is_push', ConfigJpush::IS_PUSH_FALSE)
            ->get();
        $returnData = [];
        foreach ($jpush as $jpush) {
            $returnData[] = [
                'appname' => $jpush->appname,
                'key'     => $jpush->key,
                'secret'  => $jpush->secret
            ];
        }

        return $returnData;
    }
}

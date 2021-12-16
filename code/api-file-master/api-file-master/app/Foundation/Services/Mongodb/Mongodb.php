<?php
/**
 * Created by PhpStorm.
 * User: ailuoy
 * Date: 2019/3/14
 * Time: 上午12:19
 */

namespace App\Foundation\Services\Mongodb;

use Illuminate\Support\Facades\DB;
use Jenssegers\Mongodb\Eloquent\Builder;

class Mongodb
{
    /**
     * @param        $tables
     *
     * @return Builder
     */
    public static function connectionMongodb($tables)
    {
        return DB::connection('mongodb')->collection($tables);
    }
}
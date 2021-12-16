<?php
/**
 * Created by PhpStorm.
 * User: reliy
 * Date: 2019/2/14
 * Time: 4:50 PM
 */

namespace App\Models;

use App\Foundation\Modules\SoftDelete\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

/**
 * Class Model
 * @package App\Models
 */
class BaseModel extends Eloquent
{
    use SoftDeletes;

    protected $dateFormat = 'U';
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Modify the framework native soft delete lock dependent
     * global scope to be custom scope
     */
    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope);
    }

    /**
     * 通过下标数组从模型中获取数据
     *
     * @param  array  $keys
     *
     * @return array
     */
    public function get(...$keys)
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->getAttribute($key);
        }

        return $values;
    }

    /**
     * 设置原始数据
     *
     * @param $attribute
     * @param $value
     *
     * @return $this
     */
    public function setRawOriginal($attribute, $value)
    {
        Arr::set($this->original, $attribute, $value);

        return $this;
    }
}

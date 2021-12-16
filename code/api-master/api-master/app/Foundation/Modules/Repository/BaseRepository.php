<?php
/**
 * Created by PhpStorm.
 * User: reliy
 * Date: 2019/2/13
 * Time: 2:47 PM
 */

namespace App\Foundation\Modules\Repository;

use App\Models\BaseModel;
use App\Exceptions\ServiceException;

/**
 * Class BaseRepository
 * @package App\Foundation\Modules\BaseRepository
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @var \App\Models\Model|mixed
     */
    protected $model;
    /**
     * @var \Illuminate\Database\Eloquent\Builder|null
     */
    protected $query = null;

    /**
     * BaseRepository constructor.
     *
     * BaseRepository constructor.
     * @throws ServiceException
     */
    public function __construct()
    {
        $this->makeModel();
    }

    /**
     * Instantiation model
     *
     * @return BaseModel|\Laravel\Lumen\Application|mixed
     * @throws ServiceException
     */
    public function makeModel()
    {
        $model = app($this->setModel());
        if (!$model instanceof BaseModel) {
            throw new ServiceException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * Get attribute model
     *
     * @return BaseModel|\Illuminate\Database\Eloquent\Builder
     */
    public function m()
    {
        return $this->model;
    }

    /**
     * Get Write Pdo
     *
     * @return BaseModel|\Illuminate\Database\Eloquent\Builder
     */
    public function write()
    {
        return $this->model->useWritePdo();
    }

    /**
     * Get attribute query
     *
     * @param  bool  $isNew
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getQuery($isNew = false)
    {
        return $this->query || $isNew ? $this->query : $this->model->query();
    }

    /**
     * 根据UUID获取模块数据
     *
     * @param  int             $uuid
     * @param  array|string[]  $select
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getByUUID(int $uuid, array $select = ['*'])
    {
        return $this->m()->select($select)->where('uuid', $uuid)->first();
    }

    /**
     * 根据ID获取模块数据
     *
     * @param  int             $id
     * @param  array|string[]  $select
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getById(int $id, array $select = ['*'])
    {
        return $this->m()->select($select)->where('id', $id)->first();
    }

    /**
     * 根据ID批量获取模块数据
     *
     * @param  array           $ids
     * @param  array|string[]  $select
     *
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getByIds(array $ids, array $select = ['*'])
    {
        return $this->m()->select($select)->whereIn('id', $ids)->get();
    }
}

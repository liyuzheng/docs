<?php

namespace App\Foundation\Modules\SoftDelete;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope as SystemSoftDeletingScope;

/**
 * Class SoftDeletingScope
 * @package App
 */
class SoftDeletingScope extends SystemSoftDeletingScope
{
    /**
     * Default value when the resource is not soft deleted
     *
     * @var int
     */
    const NOT_DELETE_DEFAULT_VALUE = 0;

    /**
     * Apply the scope to a given Eloquent query builder.
     * Filter soft deleted data by default
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model   $model
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where($model->getQualifiedDeletedAtColumn(), self::NOT_DELETE_DEFAULT_VALUE);
    }

    /**
     * Add the restore extension to the builder.
     * Add restore extensions to restore soft deleted data back to normal data
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return void
     */
    protected function addRestore(Builder $builder)
    {
        $builder->macro('restore', function (Builder $builder) {
            $builder->withTrashed();

            return $builder->update([$builder->getModel()->getDeletedAtColumn() => self::NOT_DELETE_DEFAULT_VALUE]);
        });
    }

    /**
     * Add the without-trashed extension to the builder.
     * Filtering data that has been soft deleted
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return void
     */
    protected function addWithoutTrashed(Builder $builder)
    {
        $builder->macro('withoutTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->where($model->getQualifiedDeletedAtColumn(),
                self::NOT_DELETE_DEFAULT_VALUE);

            return $builder;
        });
    }

    /**
     * Add the only-trashed extension to the builder.
     * Add an extension to retrieve only data that has been soft deleted
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return void
     */
    protected function addOnlyTrashed(Builder $builder)
    {
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->where($model->getQualifiedDeletedAtColumn(), '!=',
                self::NOT_DELETE_DEFAULT_VALUE);

            return $builder;
        });
    }
}
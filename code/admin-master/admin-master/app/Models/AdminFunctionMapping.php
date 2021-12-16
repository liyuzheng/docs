<?php


namespace App\Models;


class AdminFunctionMapping extends BaseModel
{
    protected $table    = 'admin_function_mapping';
    protected $fillable = [
        'key',
        'option_id',
        'times',
    ];
    protected $hidden   = ['option_id', 'created_at', 'updated_at', 'deleted_at'];

    public function option()
    {
        return $this->hasOne(Option::class, 'id', 'option_id');
    }
}

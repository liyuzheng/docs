<?php


namespace App\Models;


class AdminOperationLog extends BaseModel
{
    protected $table    = 'admin_operation_log';
    protected $fillable = [
        'admin_id',
        'path',
        'params',
        'ip',
        'header',
    ];
}

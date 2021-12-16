<?php


namespace App\Models;


class TaskPrize extends BaseModel
{
    protected $table    = 'task_prize';
    protected $fillable = [
        'task_id',
        'prize_id',
        'value'
    ];
}

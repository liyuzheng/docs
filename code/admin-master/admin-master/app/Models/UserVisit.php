<?php


namespace App\Models;


class UserVisit extends BaseModel
{
    protected $table    = 'user_visit';
    protected $fillable = ['user_id', 'related_type', 'related_id', 'visit_time'];
    protected $hidden   = ['id', 'user_id', 'related_type', 'related_id', 'visit_time'];

    const RELATED_TYPE_INTRODUCTION = 100;
}

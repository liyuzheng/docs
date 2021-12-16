<?php


namespace App\Models;


class UserLike extends BaseModel
{
    protected $table    = 'user_like';
    protected $fillable = ['related_type', 'related_id', 'user_id'];
    protected $hidden   = ['id','updated_at', 'deleted_at', 'related_type', 'related_id', 'user_id'];

    const RELATED_TYPE_MOMENT = 100;
}

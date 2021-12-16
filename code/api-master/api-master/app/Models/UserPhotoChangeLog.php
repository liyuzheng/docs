<?php


namespace App\Models;


class UserPhotoChangeLog extends BaseModel
{
    protected $table    = 'user_photo_change_log';
    protected $fillable = [
        'user_id',
        'resource_id',
        'related_type',
        'amount',
        'status'
    ];
}

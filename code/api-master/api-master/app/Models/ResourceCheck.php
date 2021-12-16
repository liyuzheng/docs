<?php


namespace App\Models;


class ResourceCheck extends BaseModel
{
    protected $table    = 'resource_check';
    protected $fillable = [
        'related_type',
        'related_id',
        'resource_id',
        'resource',
        'status'
    ];

    const RELATED_TYPE_USER_PHOTO = 100;
    const RELATED_TYPE_USER_VIDEO = 101;

    const STATUS_DELAY        = 0;
    const STATUS_PASS         = 100;
    const STATUS_PORN_FAIL    = 200;
    const STATUS_FACE_FAIL    = 201;
    const STATUS_COMPARE_FAIL = 202;

    const RELATED_TYPE_MAPPING = [
        Resource::TYPE_IMAGE => self::RELATED_TYPE_USER_PHOTO,
        Resource::TYPE_VIDEO => self::RELATED_TYPE_USER_VIDEO
    ];
}

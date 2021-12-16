<?php


namespace App\Models;


class UserPhoto extends BaseModel
{
    protected $table    = 'user_photo';
    protected $fillable = [
        'user_id',
        'resource_id',
        'related_type',
        'amount',
        'status'
    ];

    const RELATED_TYPE_FREE       = 100;
    const RELATED_TYPE_FIRE       = 200;
    const RELATED_TYPE_RED_PACKET = 300;

    const RELATED_TYPE_FIRE_STR       = 'fire';
    const RELATED_TYPE_RED_PACKET_STR = 'red_packet';
    const RELATED_TYPE_FREE_STR       = 'free';

    const STATUS_OPEN  = 1;
    const STATUS_CLOSE = 0;

    const EXTENSION_MAPPING = [
        self::RELATED_TYPE_FIRE_STR       => self::RELATED_TYPE_FIRE,
        self::RELATED_TYPE_RED_PACKET_STR => self::RELATED_TYPE_RED_PACKET,
        self::RELATED_TYPE_FREE_STR       => self::RELATED_TYPE_FREE
    ];

    const AMOUNT_MAPPING = [
        self::RELATED_TYPE_FIRE       => 3,
        self::RELATED_TYPE_RED_PACKET => 500,
        self::RELATED_TYPE_FREE       => 0
    ];
}

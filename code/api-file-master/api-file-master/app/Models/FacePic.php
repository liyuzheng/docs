<?php


namespace App\Models;


class FacePic extends BaseModel
{
    protected $table    = 'face_pic';
    protected $fillable = ['user_id', 'base_map', 'status'];

    const STATUS_PASS  = 0;
    const STATUS_BLACK = 100;
}

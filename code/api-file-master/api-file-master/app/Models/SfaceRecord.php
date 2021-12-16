<?php


namespace App\Models;


class SfaceRecord extends BaseModel
{
    protected $table    = 'sface_record';
    protected $fillable = ['person_id', 'group_id', 'face_id', 'url'];

    const GROUP_FACE_BLACK = 'xiaoquan_face_black';
}

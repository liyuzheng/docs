<?php


namespace App\Models;


class SpamWord extends BaseModel
{
    protected $table    = 'spam_word';
    protected $fillable = [
        'version',
        'word'
    ];
}

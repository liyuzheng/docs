<?php


namespace App\Models;


class StatSmsRecall extends BaseModel
{
    protected $table    = 'stat_sms_recall';
    protected $fillable = [
        'date',
        'installed_open_count',
        'uninstall_open_count'
    ];
}

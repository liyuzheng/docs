<?php


namespace App\Models;


class ReportFeedback extends BaseModel
{
    protected $table    = 'report_feedback';
    protected $fillable = ['report_id', 'related_type', 'related_id', 'content'];

    const RELATED_TYPE_REPORTED = 100;
    const RELATED_TYPE_REPORT   = 200;
}

<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Moment extends BaseModel
{
    protected $table    = 'moment';
    protected $fillable = ['uuid', 'user_id', 'topic_id', 'content', 'like_count', 'lng', 'lat', 'city', 'check_status', 'reason', 'sort', 'operator_id'];
    protected $hidden   = ['id', 'topic_id', 'user_id', 'lng', 'lat', 'operator_id', 'created_at', 'updated_at', 'deleted_at'];

    const CHECK_STATUS_DELAY       = 0;
    const CHECK_STATUS_PASS        = 100;
    const CHECK_STATUS_FAIL        = 200;
    const CHECK_STATUS_MANUAL_FAIL = 201;
    const CHECK_STATUS_USER_FAIL   = 202;

    const RELATED_TYPE_MOMENT = 100;

    const SORT_DEFAULT = 100;
    const SORT_ALL     = 1000;
    const SORT_MAN     = 1001;
    const SORT_WOMEN   = 1002;

    /**
     * 话题
     * @return belongsTo
     */
    public function topic() : belongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'id');
    }

    /**
     * 话题
     * @return belongsTo
     */
    public function user() : belongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function operator()
    {
        return $this->hasOne(Admin::class, 'id', 'operator_id');
    }

    /**
     * @param $region
     *
     * @return string
     */
    public function getCityAttribute($region) : string
    {
        return $region === '' ? '神秘星球' : $region;
    }

    public function getStatusAttribute($status)
    {
        return $status == 0 ? 'close' : 'open';
    }

    public function getUuidAttribute($uuid)
    {
        return (string)$uuid;
    }
}

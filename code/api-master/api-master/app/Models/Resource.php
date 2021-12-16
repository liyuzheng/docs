<?php


namespace App\Models;


class Resource extends BaseModel
{
    protected $table    = 'resource';
    protected $fillable = [
        'uuid',
        'related_type',
        'related_id',
        'type',
        'resource',
        'height',
        'width',
        'sort'
    ];
    protected $appends  = ['preview', 'fake_cover', 'small_cover'];
    protected $hidden   = ['id', 'related_type', 'related_id', 'sort', 'created_at', 'updated_at', 'deleted_at'];

    const RELATED_TYPE_USER_AVATAR   = 100;
    const RELATED_TYPE_USER_PHOTO    = 101;
    const RELATED_TYPE_REPORT        = 200;
    const RELATED_TYPE_FEEDBACK      = 201;
    const RELATED_TYPE_MOMENT_REPORT = 202;
    const RELATED_INVITE_QR_CODE     = 300;
    const RELATED_MOMENT             = 400;
    const RELATED_BANNER             = 500;

    const TYPE_IMAGE = 100;
    const TYPE_VOICE = 200;
    const TYPE_VIDEO = 300;
    const TYPE_LIST  = [100 => 'image', 200 => 'voice', 300 => 'video'];

    public function getTypeAttribute($type)
    {
        return self::TYPE_LIST[$type];
    }

    /**
     * 获取完整资源地址
     * @return false|int|string
     */
    public function getPreviewAttribute()
    {
        if (has_http_https($this->resource)) {
            return $this->resource;
        }

        return cdn_url($this->resource);
    }

    /**
     * 获取资源封面图
     *
     * @return mixed|string
     */
    public function getFakeCoverAttribute()
    {
        return $this->type == self::TYPE_LIST[self::TYPE_VIDEO] ? cdn_url($this->resource) . '?vframe/png/offset/0' : cdn_url($this->resource);
    }

    /**
     * 获取资源封面图
     *
     * @return mixed|string
     */
    public function getSmallCoverAttribute()
    {
        if (isset($this->attributes['small_cover'])) {
            return $this->attributes['small_cover'];
        }
        if ($this->related_type == self::RELATED_MOMENT) {
            return $this->type == self::TYPE_LIST[self::TYPE_VIDEO] ? cdn_http_url($this->resource) . '?vframe/png/offset/0/h/200' : cdn_http_url($this->resource) . '?imageMogr2/thumbnail/300/auto-orient';
        } else {
            ///thumbnail/200
            return $this->type == self::TYPE_LIST[self::TYPE_VIDEO] ? cdn_url($this->resource) . '?vframe/png/offset/0/h/200' : cdn_url($this->resource) . '?imageMogr2';
        }
    }

    /**
     * uuid转字符串
     *
     * @param $value
     *
     * @return string
     */
    public function getUuidAttribute($value)
    {
        return (string)$value;
    }

}

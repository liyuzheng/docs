<?php


namespace App\Models;


class Tag extends BaseModel
{
    protected $table    = 'tag';
    protected $hidden   = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['uuid', 'type', 'name', 'icon'];

    const TYPE_TAG_MAN      = 101;//男标签
    const TYPE_TAG_WOMEN    = 102;//女标签
    const TYPE_RELATION     = 200;//关系
    const TYPE_REPORT       = 300;//举报类型
    const TYPE_EMOTION      = 400;//情感状态
    const TYPE_CHILD        = 401;//有无孩子
    const TYPE_EDUCATION    = 402;//学历
    const TYPE_INCOME       = 403;//年收入
    const TYPE_FIGURE       = 404;//身材
    const TYPE_SMOKE        = 405;//抽烟
    const TYPE_DRINK        = 406;//饮酒
    const TYPE_HOBBY        = 407;//兴趣爱好
    const TYPE_ADMIN_REPORT = 500;//后台处理举报标签

    const DETAIL_EXTRA_MAPPING = [
        self::TYPE_EMOTION   => 'emotion',
        self::TYPE_CHILD     => 'child',
        self::TYPE_EDUCATION => 'education',
        self::TYPE_INCOME    => 'income',
        self::TYPE_FIGURE    => 'figure',
        self::TYPE_SMOKE     => 'smoke',
        self::TYPE_DRINK     => 'drink',
        self::TYPE_HOBBY     => 'hobby',
    ];

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

<?php


namespace App\Models;


class Banner extends BaseModel
{
    protected $table    = 'banner';
    protected $fillable = ['related_type', 'related_id', 'type', 'resource_id', 'version', 'os', 'role', 'sort', 'value', 'expired_at', 'publish_at'];

    public const RELATED_TYPE_MOMENT = 100;
    public const TYPE_INNER_BROWSER  = 100;//内置浏览器
    public const TYPE_OUTER_BROWSER  = 200;//外部浏览器

    public const OS_ALL     = 100;
    public const OS_ANDROID = 101;
    public const OS_IOS     = 102;

    public const ROLE_MAN               = 'man';
    public const ROLE_MEN_MEMBER        = 'man_member';
    public const ROLE_CHARM_GRIL        = 'charm_gril';
    public const ROLE_CHARM_GRIL_MEMBER = 'charm_gril_member';

    /**
     * 获取版本号
     *
     * @param  int  $number
     *
     * @return string
     */
    public function getVersionAttribute(int $number) : string
    {
        return integer_to_version($number);
    }

    /**
     * 设置版本号
     *
     * @param  string  $version
     */
    public function setVersionAttribute(string $version) : void
    {
        $this->attributes['version'] = version_to_integer($version);
    }
}

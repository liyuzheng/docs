<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends BaseModel
{
    protected $table    = 'user';
    protected $fillable = [
        'uuid',
        'number',
        'nickname',
        'area',
        'mobile',
        'birthday',
        'gender',
        'active_at',
        'role',
        'charm_girl_at'
    ];

    const ROLE_USER        = 'user';
    const ROLE_AUTH_USER   = 'auth_user';
    const ROLE_AUTH_MEMBER = 'auth_member';
    const ROLE_CHARM_GIRL  = 'charm_girl';

    const ALIYUN_TEST_THRESHOLD = 80;

    const GENDER_NOT_KNOW = 0;
    const GENDER_MAN      = 1;
    const GENDER_WOMEN    = 2;

    const ROLE_ARR = [
        'user'        => '普通用户',
        'auth_user'   => '认证用户',
        'auth_member' => '会员',
        'charm_girl'  => '魅力女生'
    ];

    const DETAIL_CHANGE_TIME        = 86400 * 5;
    const DETAIL_MEMBER_CHANGE_TIME = 86400;
    //    const DETAIL_CHANGE_TIME        = 60 * 5;
    //    const DETAIL_MEMBER_CHANGE_TIME = 60;

    const SHOW       = 0;//显示
    const ADMIN_HIDE = 3;//后台隐身
    const HIDE       = 100;//隐身


    const  MONGO_LOC_IS_UPLOAD  = 1;//上传的坐标不为0
    const  MONGO_LOC_NOT_UPLOAD = 0;//上传的坐标为0

    const UPDATE_ACTIVE_SECONDS = 5;

    protected $hidden  = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $appends = ['age'];

    public function userDetail()
    {
        return $this->hasOne(UserDetail::class, 'user_id', 'id');
    }

    public function userReview()
    {
        return $this->hasOne(UserReview::class, 'user_id', 'id');
    }

    public function userDetailExtra()
    {
        return $this->hasOne(UserDetailExtra::class, 'user_id', 'id');
    }

    /**
     * 我关注的人
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function follow()
    {
        return $this->belongsToMany(User::class, 'user_follow', 'user_id', 'follow_id');
    }

    /**
     * 判断用户当前是否是vip身份
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     *
     * @return bool
     */
    public function isMember($query = null)
    {
        if (!$query) {
            $query = rep()->member->getQuery();
        }

        $member = $query->where('user_id', $this->id)->first();

        return $member && $member->start_at + $member->duration > time();
    }

    /**
     * 所有number都返回空
     * @return false|int|string
     */
    public function getNumberAttribute($number)
    {
        return '';
    }

    /**
     * 获取年龄
     * @return false|int|string
     */
    public function getAgeAttribute()
    {
        $birthday = $this->birthday;

        return birthday_to_age(strtotime($birthday));
    }

    /**
     * 获取格式化的生日
     *
     * @param $birthday
     *
     * @return false|string
     */
    public function getBirthdayAttribute($birthday)
    {
        return date('Y-m-d', $birthday);
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


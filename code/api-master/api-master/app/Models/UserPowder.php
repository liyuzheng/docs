<?php


namespace App\Models;


class UserPowder extends BaseModel
{
    protected $table = 'user_power';

    const COMMON_APP_NAME = 'common';

    const GENDER_COMMON = 0;
    const ROLE_COMMON   = 'common';

    const MEMBER_COMMON  = 0;
    const MEMBER_VALID   = 1;
    const MEMBER_INVALID = 2;

    const TYPE_BOOLEAN = 100;
    const TYPE_STRING  = 200;
    const TYPE_JSON    = 300;

    /**
     * @param string $value
     *
     * @return mixed
     */
    public function getValueAttribute($value)
    {
        if ( $this->getRawOriginal('type') == self::TYPE_JSON ) {
            return json_decode($value, true);
        }

        return $value;
    }
}

<?php


namespace App\Models;


class InviteBuildRecord extends BaseModel
{
    protected $table = 'invite_build_record';
    protected $fillable = ['channel', 'related_type', 'user_id', 'invite_id', 'content'];

    const CHANNEL_APPLET      = InviteRecord::CHANNEL_APPLET; // 小程序邀请
    const RELATED_TYPE_MOBILE = 100;                          // 手机号绑定
}

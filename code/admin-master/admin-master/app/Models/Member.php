<?php


namespace App\Models;


class Member extends BaseModel
{
    protected $table = 'member';
    protected $primaryKey = 'user_id';
    protected $fillable = ['user_id', 'card_id', 'continuous', 'start_at', 'duration'];
    protected $hidden = ['user_id', 'card_id', 'start_at', 'duration', 'created_at', 'updated_at', 'deleted_at'];

    const TYPE_VIP = 100; // 会员卡

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function card()
    {
        return $this->hasOne(Card::class, 'id', 'card_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getExpiredAt()
    {
        return $this->getRawOriginal('start_at') + $this->getRawOriginal('duration');
    }
}

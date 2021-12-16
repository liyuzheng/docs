<?php


namespace App\Models;


class Admin extends BaseModel
{
    protected $table    = 'admin';
    protected $hidden   = ['type', 'password', 'role_id', 'created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['type', 'name', 'role_id', 'password', 'email', 'secret'];

    const TYPE_ADMIN = 100;

    public function adminRole()
    {
        return $this->hasOne(AdminRole::class, 'id', 'role_id');
    }
}

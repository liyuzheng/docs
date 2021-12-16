<?php


namespace App\Models;


class AdminRole extends BaseModel
{
    protected $table    = 'admin_role';
    protected $fillable = ['name', 'status'];
}

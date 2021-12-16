<?php


namespace App\Repositories;

use App\Models\Role;
use App\Foundation\Modules\Repository\BaseRepository;


class RoleRepository extends BaseRepository
{
    public function setModel()
    {
        return Role::class;
    }

    /**
     * 获得用户Role
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getUserRole()
    {
        return $this->m()->where('key', Role::KEY_USER)->first();
    }
}

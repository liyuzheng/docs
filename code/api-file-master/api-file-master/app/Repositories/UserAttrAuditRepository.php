<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\UserAttrAudit;

class UserAttrAuditRepository extends BaseRepository
{
    public function setModel()
    {
        return UserAttrAudit::class;
    }
}
<?php


namespace App\Repositories;


use App\Models\UserFollowOffice;
use App\Foundation\Modules\Repository\BaseRepository;

class UserFollowOfficeRepository extends BaseRepository
{
    public function setModel()
    {
        return UserFollowOffice::class;
    }

    /**
     * 根据ticket获得对象
     *
     * @param  string  $ticket
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getByTicket(string $ticket)
    {
        return rep()->userFollowOffice->getQuery()
            ->where('ticket', $ticket)
            ->orderBy('id', 'desc')
            ->first();
    }
}

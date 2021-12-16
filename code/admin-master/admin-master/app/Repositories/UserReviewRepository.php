<?php


namespace App\Repositories;


use App\Models\UserReview;
use App\Foundation\Modules\Repository\BaseRepository;

class UserReviewRepository extends BaseRepository
{
    public function setModel()
    {
        return UserReview::class;
    }

    /**
     * 获得用户最后一个userReview
     *
     * @param  int  $userId
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getLatestUserReview(int $userId)
    {
        return $this->getQuery()->where('user_id', $userId)->orderBy('id', 'desc')->first();
    }
}

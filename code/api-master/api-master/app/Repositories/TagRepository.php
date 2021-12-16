<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Tag;

class TagRepository extends BaseRepository
{
    public function setModel()
    {
        return Tag::class;
    }
}
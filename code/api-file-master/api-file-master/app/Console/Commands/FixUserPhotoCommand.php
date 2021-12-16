<?php

namespace App\Console\Commands;

use App\Models\Resource;
use App\Models\UserPhoto;
use Illuminate\Console\Command;

/**
 * 修改位置
 * Class FillCoordinateCommand
 * @package App\Console\Commands
 */
class FixUserPhotoCommand extends Command
{
    protected $signature   = 'xiaoquan:fix_user_photo {sid} {tid}';
    protected $description = '修改位置';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $sid = $this->argument('sid');
        $tid = $this->argument('tid');
        for ($i = $sid; $i <= $tid; $i++) {
            $resource = rep()->resource->m()->find($i);
            if (!$resource) {
                $this->error($i . ' 资源不存在');
                continue;
            }
            if ($resource->getOriginal('related_type') !== Resource::RELATED_TYPE_USER_PHOTO) {
                $this->error($i . ' 不是用户相册');
                continue;
            }
            $userPhoto = rep()->userPhoto->m()->where('resource_id', $resource->id)->first();
            if ($userPhoto) {
                $this->error($i . ' 图片已经存在');
                continue;
            }
            $values = [
                'user_id'      => $resource->related_id,
                'resource_id'  => $resource->id,
                'related_type' => UserPhoto::RELATED_TYPE_FREE,
                'amount'       => 0,
                'status'       => UserPhoto::STATUS_OPEN,
                'created_at'   => $resource->created_at->timestamp,
                'updated_at'   => $resource->updated_at->timestamp
            ];
            if ($upId = rep()->userPhoto->m()->insertGetId($values)) {
                $this->info(get_command_output_date() . ' id: ' . $upId);
            }
        }
    }
}

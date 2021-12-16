<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Resource;
use Illuminate\Console\Command;
use Intervention\Image\Facades\Image;

/**
 * 给历史所有女生的头像和相册的水印
 * Class FixUserWaterMarkCommand
 * @package App\Console\Commands
 */
class FixUserWaterMarkCommand extends Command
{
    protected $signature   = 'xiaoquan:fix_user_watermark';
    protected $description = '给历史所有女生的头像和相册的水印';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $users = rep()->user->m()
            ->where('gender', User::GENDER_WOMEN)
            ->get();
        foreach ($users as $user) {
            $resources = rep()->resource->m()
                ->whereIn('related_type', [Resource::RELATED_TYPE_USER_AVATAR, Resource::RELATED_TYPE_USER_PHOTO])
                ->where('related_id', $user->id)
                ->get();
            foreach ($resources as $resource) {
                if (file_exists(storage_path($resource->resource))) {
                    $extension   = explode('.', $resource->resource)[1] ?? 'jpeg';
                    $one         = explode('/', $resource->resource);
                    $name        = $one[count($one) - 1];
                    $newFileName = str_replace($name, get_md5_random_str() . '.' . $extension, $resource->resource);
                    $imgInfo     = getimagesize(storage_path($resource->resource));
                    $width       = $imgInfo[0] ?? 338;
                    $height      = $imgInfo[1] ?? 565;
                    $y           = intval(($height / 2) * 0.7);
                    $tmpImg      = Image::make(storage_path($resource->resource));
                    $water       = Image::make(storage_path(config('custom.common_image_path.watermark.path')))->resize(intval($width / 4), intval($width / 12));
                    $tmpImg->insert($water, 'bottom-right', 0, $y);
                    storage()->put(storage_path($newFileName), $tmpImg->save(storage_path($newFileName)));
                    rep()->resource->m()->where('id', $resource->id)->update([
                        'resource' => $newFileName,
                    ]);
                    $this->line('before_name:' . $resource->resource . ':after_name:' . $newFileName);
                    $this->line('user_id:' . $user->id . ':resource_id:' . $resource->id . '：已经更新！');
                }
            }

        }

    }
}

<?php


namespace App\Jobs;

use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Resource;
use App\Models\Moment;

class CheckMomentJob extends Job
{
    private $momentId;

    public function __construct($momentId)
    {
        $this->momentId = $momentId;
    }

    public function handle()
    {
        $moment = rep()->moment->getById($this->momentId);
        if (!$moment) {
            $job = (new CheckMomentJob($this->momentId))
                ->onQueue('check_moment');
            dispatch($job);

            return ResultReturn::failed(trans('messages.not_found_moment'));
        }
        $user           = rep()->user->getById($moment->user_id);
        $contentStatus  = false;
        $resourceStatus = true;
        $momentResource = rep()->resource->m()
            ->where('related_type', Resource::RELATED_MOMENT)
            ->where('related_id', $this->momentId)
            ->get()
            ->pluck('resource')
            ->toArray();
        $momentContent  = $moment->content;
        $contentResult  = pocket()->neteaseDun->checkText(get_md5_random_str(), $momentContent,
            config('netease.keys.dun.moment_text'), $user->uuid);
        $contentFail    = false;
        $pictureFail    = false;
        if ($contentResult->getStatus() == true) {
            $checkData   = $contentResult->getData();
            $checkStatus = $checkData['check_status'];
            if ($checkStatus == 100) {
                $contentStatus = true;
            } else {
                $contentFail = true;
            }
        }
        $resourceResult = pocket()->neteaseDun->checkImages($momentResource, config('netease.keys.dun.moment_pic'),
            $user->uuid);
        $pornStatusData = $resourceResult->getData();
        if (key_exists('check_status', $pornStatusData)) {
            $pornStatus = $pornStatusData['check_status'];
            foreach ($pornStatus as $item) {
                if ($item == 2) {
                    $resourceStatus = false;
                    $pictureFail    = true;
                }
            }
        } else {
            $resourceStatus = false;
        }

        if ($contentStatus && $resourceStatus) {
            rep()->moment->getById($this->momentId)->update(['check_status' => Moment::CHECK_STATUS_PASS]);
            pocket()->esMoment->updateMomentFieldToEs($this->momentId, ['check_status' => Moment::CHECK_STATUS_PASS]);
            pocket()->netease->msgSendMsg(config('custom.little_helper_uuid'), $user->uuid, '动态发布成功~');
        } else {
            rep()->moment->getById($this->momentId)->update(['check_status' => Moment::CHECK_STATUS_FAIL]);
            pocket()->esMoment->updateMomentFieldToEs($this->momentId, ['check_status' => Moment::CHECK_STATUS_FAIL]);
            $message = '';
            if ($contentFail && $pictureFail) {
                $message = trans('messages.moment_img_and_text_law_forbid', [], $user->language);
            } elseif ($contentFail && !$pictureFail) {
                $message = trans('messages.moment_img_law_forbid_edit', [], $user->language);
            } elseif (!$contentFail && $pictureFail) {
                $message = trans('messages.moment_img_law_forbid', [], $user->language);
            }
            pocket()->netease->msgSendMsg(config('custom.little_helper_uuid'), $user->uuid, $message);
        }

        return ResultReturn::success([]);
    }
}

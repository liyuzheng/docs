<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class UserTagPocket extends BasePocket
{
    /**
     * 给用户添加假评论
     *
     * @param $rUserId
     * @param $tagId
     * @param $grade
     *
     * @return ResultReturn
     */
    public function addFakeEvaluate($rUserId, $tagId, $grade)
    {
        $fakeEvaluateUser = explode(',', config('custom.fake_evaluate_user'));
        $grade            = (float)$grade;
        if ($grade == 5) {
            return ResultReturn::success([]);
        }
        $fakeGradeMapping = [
            0.5 => [
                'min' => 3.0,
                'max' => 3.2
            ],
            1.0 => [
                'min' => 3.2,
                'max' => 3.4
            ],
            1.5 => [
                'min' => 3.4,
                'max' => 3.6
            ],
            2.0 => [
                'min' => 3.6,
                'max' => 3.8
            ],
            2.5 => [
                'min' => 3.8,
                'max' => 4.0
            ],
            3.0 => [
                'min' => 4.0,
                'max' => 4.2
            ],
            3.5 => [
                'min' => 4.2,
                'max' => 4.4
            ],
            4.0 => [
                'min' => 4.4,
                'max' => 4.6
            ],
            4.5 => [
                'min' => 4.6,
                'max' => 4.8
            ],
            5.0 => [
                'min' => 5.0,
                'max' => 5.0
            ]
        ];
        $existUser        = rep()->userEvaluate->m()
            ->where('target_user_id', $rUserId)
            ->where('tag_id', $tagId)
            ->whereIn('user_id', $fakeEvaluateUser)
            ->get();
        $fakeUsers        = array_diff($fakeEvaluateUser, $existUser->pluck('user_id')->toArray());
        if (count($fakeUsers) == 0) {
            return ResultReturn::failed('假评论用户不足');
        }
        $this->setFakeEvaluate($fakeUsers, $rUserId, $tagId, $grade, $fakeGradeMapping[$grade]['min'], $fakeGradeMapping[$grade]['max']);

        return ResultReturn::success([]);
    }

    /**
     * 给用户设置假评论
     *
     * @param $fakeUser
     * @param $rUserId
     * @param $tagId
     * @param $grade
     * @param $min
     * @param $max
     *
     * @return ResultReturn
     */
    public function setFakeEvaluate($fakeUser, $rUserId, $tagId, $grade, $min, $max)
    {
        $count     = 1;
        $fakeGrade = 5;
        while (true) {
            if (count($fakeUser) == 0) {
                break;
            }
            if ((($grade + $fakeGrade) / ($count + 1)) <= $max) {
                $count++;
                $grade      += $fakeGrade;
                $addUserKey = array_rand($fakeUser);
                rep()->userEvaluate->m()->create([
                    'uuid'           => pocket()->util->getSnowflakeId(),
                    'user_id'        => $fakeUser[$addUserKey],
                    'target_user_id' => $rUserId,
                    'tag_id'         => $tagId,
                    'star'           => $fakeGrade
                ]);
                unset($fakeUser[$addUserKey]);
            } else {
                $fakeGrade -= 0.5;
            }
            if ($grade / $count > $min) {
                break;
            }
        }

        return ResultReturn::success([]);
    }
}

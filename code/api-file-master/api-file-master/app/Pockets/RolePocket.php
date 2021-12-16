<?php


namespace App\Pockets;

use App\Models\Role;
use App\Foundation\Modules\Pocket\BasePocket;

/**
 * Class RolePocket
 * @package App\Pockets
 */
class RolePocket extends BasePocket
{
    /**
     * 穷举角色
     *
     * @param        $include  array 包含的角色
     * @param        $except   array 排除的角色
     *
     * @param  bool  $roleStr  是否返回角色的字符串 ，默认是穷举后的二次方number
     *
     * @return array|bool
     */
    public function getUserRoleArr(array $include = [], array $except = [], $roleStr = false)
    {
        $commonRoles = rep()->role->m()
            ->select(['key'])
            ->where('type', Role::TYPE_COMMON)
            ->pluck('key');
        $count       = count($commonRoles);
        $min         = $count;

        if ($min < 1) {
            return [];
        }

        $roleRet = [];
        for (; $min >= 1; $min--) {
            $arrRet = array();
            $max    = $count - ($min - 1);
            for ($i = 0; $i < $max; $i++) {
                $this->getUserRoleSub($commonRoles, $count, $min, $i, $arrRet, $roleRet, $include, $except);
            }
        }
        $weights = $commonRoles->flip()->map(function ($item) {
            return pow(2, $item);
        });
        $return  = [];
        foreach ($roleRet as $role) {
            $roleArr = explode(',', $role);
            $sum     = 0;
            foreach ($roleArr as $roleKey) {
                $sum += $weights[$roleKey] ?? 0;
            }
            $return[] = $sum;
        }

        return $roleStr ? $roleRet : $return;

    }

    /**
     * 循环查找角色
     *
     * @param $commonRoles
     * @param $count
     * @param $min
     * @param $i
     * @param $arrRet
     * @param $return
     * @param $include
     * @param $except
     *
     * @return bool
     */
    private function getUserRoleSub($commonRoles, $count, $min, $i, $arrRet, &$return, $include, $except)
    {
        if (empty($commonRoles) || empty($count))
            return false;
        if (1 == $min) {
            $arrRet[--$min] = $commonRoles[$i];
            if (!array_diff($include, $arrRet)) {
                if (!array_intersect($except, $arrRet)) {
                    $enum = [];
                    foreach ($commonRoles as $role) {
                        if (in_array($role, $arrRet)) {
                            $enum[] = $role;
                        }
                    }
                    $enum ? $return[] = implode(',', $enum) : [];
                }
            }
        } else {
            $arrRet[--$min] = $commonRoles[$i];
            for ($j = $i + 1; $j < ($count); $j++) {
                $this->getUserRoleSub($commonRoles, $count, $min, $j, $arrRet, $return, $include, $except);
            }
        }
    }
}

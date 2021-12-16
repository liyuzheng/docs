<?php


namespace App\Repositories;

use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\Option;

class OptionRepository extends BaseRepository
{
    public function setModel()
    {
        return Option::class;
    }


    /**
     * 客服快捷回复话术创建添加
     * @param $data
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     *
     */
    public function createCustomerServiceScript($data){
        return $this->m()->updateOrCreate($data);
    }

    /**
     * 客服快捷回复话术通过类型查询
     *
     * @param $type
     *
     * @return array
     */
    public function showCustomerServiceScript($type)
    {
        $data = $this->m()->where('type',$type)->get();
        $newData = [];
        foreach ($data as $key=>$value){
            unset($value['p_id'],$value['code'],$value['id']);
            $newData[] = $value['name'];
        }
        return $newData;
    }

    /**
     * 客服快捷回复话术修改
     *
     * @param $data
     *
     * @return \App\Models\BaseModel|\App\Models\Model|bool|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|int
     *
     */
    public function updateCustomerServiceScript($data,$oldName){
        return $this->m()->where("name",$oldName)->where("type",$data["type"])->update($data);
    }

    /**
     *
     * @param $data
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     *
     */
    public function deleteCustomerServiceScript($data){
        return $this->m()->where("name",$data["name"])->where("type",$data["type"])->delete();
    }
}

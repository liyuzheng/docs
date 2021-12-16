<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Admin\DiscountRequest;

class DiscountController extends BaseController
{
    /**
     * 折扣列表
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function index(Request $request) : JsonResponse
    {
        $limit = $request->get('limit', 10);
        $page  = $request->get('page', 1);
        $topic = rep()->discount->m()
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();
        $count = rep()->discount->m()
            ->count();

        return api_rr()->getOK(['all_count' => $count, 'data' => $topic]);
    }

    /**
     * 新增一条折扣记录
     *
     * @param  DiscountRequest  $request
     *
     * @return JsonResponse
     */
    public function store(DiscountRequest $request) : JsonResponse
    {
        $discount = rep()->discount->m()->create([
            'related_type' => $request->get('related_type'),
            'platform'     => $request->get('platform'),
            'discount'     => $request->get('discount'),
        ]);

        return api_rr()->postOK($discount);
    }

    /**
     * 查看一条折扣记录
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function show(Request $request, $id) : JsonResponse
    {
        $discount = rep()->discount->m()->where('id', $id)->first();

        return api_rr()->postOK($discount);
    }

    /**
     * 删除一条折扣记录
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, $id) : JsonResponse
    {
        rep()->discount->m()->where('id', $id)->update([
            'deleted_at' => time()
        ]);

        return api_rr()->deleteOK([]);
    }
}

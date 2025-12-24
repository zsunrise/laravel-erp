<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * 获取货币列表
     *
     * @param Request $request 请求对象，支持 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的货币列表，默认按是否默认排序
     */
    public function index(Request $request)
    {
        $query = Currency::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->where('is_active', true)->orderBy('is_default', 'desc')->paginate($request->get('per_page', 1000)));
    }

    /**
     * 获取指定货币详情
     *
     * @param int $id 货币ID
     * @return \Illuminate\Http\JsonResponse 返回货币详细信息
     */
    public function show($id)
    {
        $currency = Currency::findOrFail($id);
        return ApiResponse::success($currency, '获取成功');
    }
}


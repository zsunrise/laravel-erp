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
        // 构建查询
        $query = Currency::query();

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 返回激活状态的货币列表，默认货币排在前面
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
        // 根据ID查询货币，找不到则抛出404
        $currency = Currency::findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($currency, '获取成功');
    }
}


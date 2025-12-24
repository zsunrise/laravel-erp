<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * 获取单位列表
     *
     * @param Request $request 请求对象，支持 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的单位列表，按排序字段排序
     */
    public function index(Request $request)
    {
        $query = Unit::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->where('is_active', true)->orderBy('sort')->paginate($request->get('per_page', 1000)));
    }

    /**
     * 获取指定单位详情
     *
     * @param int $id 单位ID
     * @return \Illuminate\Http\JsonResponse 返回单位详细信息
     */
    public function show($id)
    {
        $unit = Unit::findOrFail($id);
        return ApiResponse::success($unit, '获取成功');
    }
}


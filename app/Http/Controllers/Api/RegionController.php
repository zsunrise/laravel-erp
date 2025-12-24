<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * 获取区域列表
     *
     * @param Request $request 请求对象，支持 parent_id（父区域ID）、level（级别）、is_active（是否激活）和 tree（树形结构）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的区域列表或树形结构
     */
    public function index(Request $request)
    {
        $query = Region::query();

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->whereNull('parent_id');
        }

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('tree')) {
            $regions = Region::whereNull('parent_id')
                ->with('children')
                ->where('is_active', true)
                ->orderBy('sort')
                ->get();
            return response()->json($regions);
        }

        return response()->json($query->where('is_active', true)->orderBy('sort')->paginate($request->get('per_page', 1000)));
    }

    /**
     * 获取指定区域详情
     *
     * @param int $id 区域ID
     * @return \Illuminate\Http\JsonResponse 返回区域详细信息，包含父区域和子区域信息
     */
    public function show($id)
    {
        $region = Region::with(['parent', 'children'])->findOrFail($id);
        return ApiResponse::success($region, '获取成功');
    }
}


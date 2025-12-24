<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * 获取仓库列表
     *
     * @param Request $request 请求对象，支持 is_active（是否激活）、is_default（是否默认）和 search（搜索关键词）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的仓库列表，包含区域信息
     */
    public function index(Request $request)
    {
        $query = Warehouse::with(['region']);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('is_default')) {
            $query->where('is_default', $request->is_default);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建新仓库
     *
     * @param Request $request 请求对象，包含仓库信息（名称、编码、区域等）
     * @return \Illuminate\Http\JsonResponse 返回创建的仓库信息，状态码 201
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:warehouses,code',
            'region_id' => 'nullable|exists:regions,id',
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        if ($validated['is_default'] ?? false) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        $warehouse = Warehouse::create($validated);

        return response()->json($warehouse->load('region'), 201);
    }

    /**
     * 获取指定仓库详情
     *
     * @param int $id 仓库ID
     * @return \Illuminate\Http\JsonResponse 返回仓库详细信息，包含区域和库位信息
     */
    public function show($id)
    {
        $warehouse = Warehouse::with(['region', 'locations'])->findOrFail($id);
        return ApiResponse::success($warehouse, '获取成功');
    }

    /**
     * 更新仓库信息
     *
     * @param Request $request 请求对象，包含要更新的仓库字段
     * @param int $id 仓库ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的仓库信息
     */
    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:warehouses,code,' . $id,
            'region_id' => 'nullable|exists:regions,id',
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        if (($validated['is_default'] ?? false) && !$warehouse->is_default) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        $warehouse->update($validated);

        return response()->json($warehouse->load('region'));
    }

    /**
     * 删除仓库
     *
     * @param int $id 仓库ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，如果仓库下有库存则返回错误消息
     */
    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);

        if ($warehouse->inventory()->count() > 0) {
            return response()->json(['message' => '该仓库下有库存，无法删除'], 400);
        }

        $warehouse->delete();

        return response()->json(['message' => '仓库删除成功']);
    }
}

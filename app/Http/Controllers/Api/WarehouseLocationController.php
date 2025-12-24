<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;

class WarehouseLocationController extends Controller
{
    /**
     * 获取库位列表
     *
     * @param Request $request 请求对象，支持 warehouse_id（仓库ID）和 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的库位列表，按排序字段排序
     */
    public function index(Request $request)
    {
        $query = WarehouseLocation::query();

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->orderBy('sort')->paginate($request->get('per_page', 1000)));
    }

    /**
     * 创建库位
     *
     * @param Request $request 请求对象，包含库位信息（仓库ID、名称、编码等）
     * @return \Illuminate\Http\JsonResponse 返回创建的库位信息，状态码 201，编码重复时返回错误消息
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'sort' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $warehouse = \App\Models\Warehouse::findOrFail($validated['warehouse_id']);
        
        $exists = WarehouseLocation::where('warehouse_id', $validated['warehouse_id'])
            ->where('code', $validated['code'])
            ->exists();
            
        if ($exists) {
            return response()->json(['message' => '该仓库下已存在相同编码的库位'], 400);
        }

        $location = WarehouseLocation::create($validated);

        return response()->json($location, 201);
    }

    /**
     * 获取指定库位详情
     *
     * @param int $id 库位ID
     * @return \Illuminate\Http\JsonResponse 返回库位详细信息
     */
    public function show($id)
    {
        $location = WarehouseLocation::findOrFail($id);
        return ApiResponse::success($location, '获取成功');
    }

    /**
     * 更新库位信息
     *
     * @param Request $request 请求对象，包含要更新的库位字段
     * @param int $id 库位ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的库位信息，编码重复时返回错误消息
     */
    public function update(Request $request, $id)
    {
        $location = WarehouseLocation::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255',
            'sort' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['code']) && $validated['code'] != $location->code) {
            $exists = WarehouseLocation::where('warehouse_id', $location->warehouse_id)
                ->where('code', $validated['code'])
                ->where('id', '!=', $id)
                ->exists();
                
            if ($exists) {
                return response()->json(['message' => '该仓库下已存在相同编码的库位'], 400);
            }
        }

        $location->update($validated);

        return response()->json($location);
    }

    /**
     * 删除库位
     *
     * @param int $id 库位ID
     * @return \Illuminate\Http\JsonResponse 返回删除成功消息
     */
    public function destroy($id)
    {
        $location = WarehouseLocation::findOrFail($id);
        $location->delete();

        return response()->json(['message' => '库位删除成功']);
    }
}


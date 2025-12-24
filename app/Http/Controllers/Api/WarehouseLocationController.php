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
     * @queryParam warehouse_id integer 仓库ID Example: 1
     * @queryParam is_active boolean 是否激活（1:激活, 0:未激活） Example: 1
     * @queryParam per_page integer 每页数量 Example: 1000
     * @param Request $request 请求对象，支持 warehouse_id（仓库ID）和 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的库位列表，按排序字段排序
     */
    public function index(Request $request)
    {
        // 构建查询
        $query = WarehouseLocation::query();

        // 按仓库ID筛选
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 按排序字段排序，返回分页结果
        return response()->json($query->orderBy('sort')->paginate($request->get('per_page', 1000)));
    }

    /**
     * 创建库位
     *
     * @bodyParam warehouse_id integer required 仓库ID Example: 1
     * @bodyParam name string required 库位名称 Example: A区-01-01
     * @bodyParam code string required 库位编码（同一仓库内唯一） Example: A-01-01
     * @bodyParam sort integer 排序值 Example: 1
     * @bodyParam is_active boolean 是否激活 Example: true
     * @param Request $request 请求对象，包含库位信息（仓库ID、名称、编码等）
     * @return \Illuminate\Http\JsonResponse 返回创建的库位信息，状态码 201，编码重复时返回错误消息
     */
    public function store(Request $request)
    {
        // 验证库位信息参数
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',   // 仓库ID（必填）
            'name' => 'required|string|max:255',                  // 库位名称（必填）
            'code' => 'required|string|max:255',                  // 库位编码（必填）
            'sort' => 'nullable|integer|min:0',                   // 排序值
            'is_active' => 'sometimes|boolean',                   // 是否激活
        ]);

        // 验证仓库存在
        $warehouse = \App\Models\Warehouse::findOrFail($validated['warehouse_id']);
        
        // 检查该仓库下是否已存在相同编码的库位
        $exists = WarehouseLocation::where('warehouse_id', $validated['warehouse_id'])
            ->where('code', $validated['code'])
            ->exists();
            
        if ($exists) {
            return response()->json(['message' => '该仓库下已存在相同编码的库位'], 400);
        }

        // 创建库位记录
        $location = WarehouseLocation::create($validated);

        // 返回新建库位信息
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
        // 根据ID查询库位，找不到则抛出404
        $location = WarehouseLocation::findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($location, '获取成功');
    }

    /**
     * 更新库位信息
     *
     * @bodyParam name string 库位名称 Example: A区-01-01
     * @bodyParam code string 库位编码（同一仓库内唯一，排除当前库位） Example: A-01-01
     * @bodyParam sort integer 排序值 Example: 1
     * @bodyParam is_active boolean 是否激活 Example: true
     * @param Request $request 请求对象，包含要更新的库位字段
     * @param int $id 库位ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的库位信息，编码重复时返回错误消息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询库位
        $location = WarehouseLocation::findOrFail($id);

        // 验证更新参数
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255',
            'sort' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        // 如果修改了编码，检查新编码在该仓库下是否已存在
        if (isset($validated['code']) && $validated['code'] != $location->code) {
            $exists = WarehouseLocation::where('warehouse_id', $location->warehouse_id)
                ->where('code', $validated['code'])
                ->where('id', '!=', $id)
                ->exists();
                
            if ($exists) {
                return response()->json(['message' => '该仓库下已存在相同编码的库位'], 400);
            }
        }

        // 更新库位信息
        $location->update($validated);

        // 返回更新后的库位信息
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
        // 根据ID查询库位
        $location = WarehouseLocation::findOrFail($id);
        // 删除库位记录
        $location->delete();

        // 返回删除成功消息
        return response()->json(['message' => '库位删除成功']);
    }
}


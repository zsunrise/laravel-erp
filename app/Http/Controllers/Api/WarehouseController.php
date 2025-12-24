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
        // 构建查询，预加载区域信息
        $query = Warehouse::with(['region']);

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 按默认仓库筛选
        if ($request->has('is_default')) {
            $query->where('is_default', $request->is_default);
        }

        // 关键词搜索：按名称或编码模糊匹配
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 返回分页结果
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
        // 验证仓库信息参数
        $validated = $request->validate([
            'name' => 'required|string|max:255',                          // 仓库名称（必填）
            'code' => 'required|string|max:255|unique:warehouses,code',   // 仓库编码（唯一）
            'region_id' => 'nullable|exists:regions,id',                  // 所属区域
            'address' => 'nullable|string|max:500',                       // 地址
            'contact_person' => 'nullable|string|max:255',                // 联系人
            'contact_phone' => 'nullable|string|max:20',                  // 联系电话
            'is_default' => 'sometimes|boolean',                          // 是否默认仓库
            'is_active' => 'sometimes|boolean',                           // 是否激活
            'description' => 'nullable|string',                           // 描述
        ]);

        // 如果设置为默认仓库，先清除其他仓库的默认标记
        if ($validated['is_default'] ?? false) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        // 创建仓库记录
        $warehouse = Warehouse::create($validated);

        // 返回新建仓库信息（包含区域）
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
        // 根据ID查询仓库，预加载区域和库位信息，找不到则抛出404
        $warehouse = Warehouse::with(['region', 'locations'])->findOrFail($id);
        // 返回标准化成功响应
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
        // 根据ID查询仓库，找不到则抛出404
        $warehouse = Warehouse::findOrFail($id);

        // 验证更新参数
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

        // 如果设置为默认仓库且当前不是默认，清除其他仓库的默认标记
        if (($validated['is_default'] ?? false) && !$warehouse->is_default) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        // 更新仓库信息
        $warehouse->update($validated);

        // 返回更新后的仓库信息
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
        // 根据ID查询仓库
        $warehouse = Warehouse::findOrFail($id);

        // 检查是否有库存记录，有则不允许删除
        if ($warehouse->inventory()->count() > 0) {
            return response()->json(['message' => '该仓库下有库存，无法删除'], 400);
        }

        // 删除仓库记录
        $warehouse->delete();

        // 返回删除成功消息
        return response()->json(['message' => '仓库删除成功']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Unit;
use App\Models\Product;
use App\Models\BomItem;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * 获取单位列表
     *
     * @queryParam is_active boolean 是否激活（1:激活, 0:未激活） Example: 1
     * @queryParam per_page integer 每页数量 Example: 1000
     * @param Request $request 请求对象，支持 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的单位列表，按排序字段排序
     */
    public function index(Request $request)
    {
        // 构建查询
        $query = Unit::query();

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 搜索功能：按名称或编码搜索
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 如果没有指定is_active，默认只返回激活状态的单位
        if (!$request->has('is_active')) {
            $query->where('is_active', true);
        }

        // 返回单位列表，按排序字段排序
        return response()->json($query->orderBy('sort')->orderBy('code')->paginate($request->get('per_page', 1000)));
    }

    /**
     * 创建新单位
     *
     * @bodyParam name string required 单位名称 Example: 个
     * @bodyParam code string required 单位编码（唯一） Example: PCS
     * @bodyParam symbol string 单位符号 Example: 个
     * @bodyParam sort integer 排序值 Example: 1
     * @bodyParam is_active boolean 是否激活 Example: true
     * @param Request $request 请求对象，包含单位信息
     * @return \Illuminate\Http\JsonResponse 返回创建的单位信息，状态码 201
     */
    public function store(Request $request)
    {
        // 验证单位信息参数
        $validated = $request->validate([
            'name' => 'required|string|max:255',                      // 单位名称（必填）
            'code' => 'required|string|max:255|unique:units,code',    // 单位编码（唯一）
            'symbol' => 'nullable|string|max:50',                    // 单位符号
            'sort' => 'nullable|integer|min:0',                       // 排序值
            'is_active' => 'sometimes|boolean',                      // 是否激活
        ]);

        // 创建单位记录
        $unit = Unit::create($validated);

        // 返回新建单位信息
        return response()->json($unit, 201);
    }

    /**
     * 获取指定单位详情
     *
     * @param int $id 单位ID
     * @return \Illuminate\Http\JsonResponse 返回单位详细信息
     */
    public function show($id)
    {
        // 根据ID查询单位，找不到则抛出404
        $unit = Unit::findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($unit, '获取成功');
    }

    /**
     * 更新单位信息
     *
     * @bodyParam name string 单位名称 Example: 个
     * @bodyParam code string 单位编码（唯一，排除当前单位） Example: PCS
     * @bodyParam symbol string 单位符号 Example: 个
     * @bodyParam sort integer 排序值 Example: 1
     * @bodyParam is_active boolean 是否激活 Example: true
     * @param Request $request 请求对象，包含要更新的单位字段
     * @param int $id 单位ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的单位信息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询单位
        $unit = Unit::findOrFail($id);

        // 验证更新参数
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:units,code,' . $id,
            'symbol' => 'nullable|string|max:50',
            'sort' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        // 更新单位信息
        $unit->update($validated);

        // 返回更新后的单位信息
        return response()->json($unit);
    }

    /**
     * 删除单位
     *
     * @param int $id 单位ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，如果单位被使用则返回错误消息
     */
    public function destroy($id)
    {
        // 根据ID查询单位
        $unit = Unit::findOrFail($id);

        // 检查是否有关联的商品，有则不允许删除
        if (Product::where('unit_id', $id)->count() > 0) {
            return response()->json(['message' => '该单位下有关联的商品，无法删除'], 400);
        }

        // 检查是否有关联的BOM项，有则不允许删除
        if (BomItem::where('unit_id', $id)->count() > 0) {
            return response()->json(['message' => '该单位下有关联的BOM项，无法删除'], 400);
        }

        // 删除单位记录
        $unit->delete();

        // 返回删除成功消息
        return response()->json(['message' => '单位删除成功']);
    }
}


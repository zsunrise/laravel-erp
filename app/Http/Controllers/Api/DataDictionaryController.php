<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\DataDictionary;
use Illuminate\Http\Request;

class DataDictionaryController extends Controller
{
    /**
     * 获取数据字典列表
     *
     * @queryParam type string 字典类型 Example: order_status
     * @queryParam code string 编码（模糊匹配） Example: pending
     * @queryParam label string 标签（模糊匹配） Example: 待处理
     * @queryParam is_active boolean 是否激活（1:激活, 0:未激活） Example: 1
     * @queryParam group_by_type boolean 是否按类型分组（1:是, 0:否） Example: 1
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 type（类型）、code（编码）、label（标签）、is_active（是否激活）和 group_by_type（按类型分组）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的字典列表或按类型分组的字典数据
     */
    public function index(Request $request)
    {
        // 构建查询
        $query = DataDictionary::query();

        // 按字典类型筛选
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // 按编码模糊筛选
        if ($request->has('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        // 按标签模糊筛选
        if ($request->has('label')) {
            $query->where('label', 'like', '%' . $request->label . '%');
        }

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 如果请求按类型分组，返回分组后的数据
        if ($request->has('group_by_type') && $request->group_by_type) {
            $dictionaries = $query->orderBy('type')->orderBy('sort')->get();
            $grouped = $dictionaries->groupBy('type'); // 按类型分组
            return ApiResponse::success($grouped);
        }

        // 按类型、排序、ID排序，返回分页结果
        return response()->json(
            $query->orderBy('type')
                ->orderBy('sort')
                ->orderBy('id')
                ->paginate($request->get('per_page', 15))
        );
    }

    /**
     * 创建数据字典项
     *
     * @bodyParam type string required 字典类型 Example: order_status
     * @bodyParam code string required 编码（同一类型下唯一） Example: pending
     * @bodyParam label string required 标签 Example: 待处理
     * @bodyParam value string required 值 Example: pending
     * @bodyParam sort integer 排序值 Example: 1
     * @bodyParam is_active boolean 是否激活 Example: true
     * @bodyParam description string 描述 Example: 订单待处理状态
     * @param Request $request 请求对象，包含字典项信息（类型、编码、标签、值等）
     * @return \Illuminate\Http\JsonResponse 返回创建的字典项信息，状态码 201，失败时返回错误消息
     */
    public function store(Request $request)
    {
        // 验证字典项参数
        $validated = $request->validate([
            'type' => 'required|string|max:50',       // 类型（必填）
            'code' => 'required|string|max:50',       // 编码（必填）
            'label' => 'required|string|max:100',     // 标签（必填）
            'value' => 'required|string|max:255',     // 值（必填）
            'sort' => 'nullable|integer|min:0',       // 排序
            'is_active' => 'sometimes|boolean',       // 是否激活
            'description' => 'nullable|string',       // 描述
        ]);

        // 检查同一类型下code是否唯一
        $exists = DataDictionary::where('type', $validated['type'])
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return ApiResponse::error('该类型下编码已存在');
        }

        try {
            // 创建字典项记录
            $dictionary = DataDictionary::create([
                'type' => $validated['type'],
                'code' => $validated['code'],
                'label' => $validated['label'],
                'value' => $validated['value'],
                'sort' => $validated['sort'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
                'description' => $validated['description'] ?? null,
            ]);

            // 返回创建成功响应
            return ApiResponse::success($dictionary, '创建成功', 201);
        } catch (\Exception $e) {
            // 创建失败返回错误消息
            return ApiResponse::error('创建失败：' . $e->getMessage());
        }
    }

    /**
     * 获取指定字典项详情
     *
     * @param int $id 字典项ID
     * @return \Illuminate\Http\JsonResponse 返回字典项详细信息
     */
    public function show($id)
    {
        // 根据ID查询字典项，找不到则抛出404
        $dictionary = DataDictionary::findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($dictionary);
    }

    /**
     * 更新字典项信息
     *
     * @param Request $request 请求对象，包含要更新的字典项字段
     * @param int $id 字典项ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的字典项信息，失败时返回错误消息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询字典项
        $dictionary = DataDictionary::findOrFail($id);

        // 验证更新参数
        $validated = $request->validate([
            'type' => 'sometimes|string|max:50',
            'code' => 'sometimes|string|max:50',
            'label' => 'sometimes|string|max:100',
            'value' => 'sometimes|string|max:255',
            'sort' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        // 如果修改了type或code，检查唯一性
        if (isset($validated['type']) || isset($validated['code'])) {
            $type = $validated['type'] ?? $dictionary->type;
            $code = $validated['code'] ?? $dictionary->code;

            // 排除自身，检查同类型下是否存在相同编码
            $exists = DataDictionary::where('type', $type)
                ->where('code', $code)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return ApiResponse::error('该类型下编码已存在');
            }
        }

        try {
            // 更新字典项信息
            $dictionary->update($validated);
            // 返回更新成功响应
            return ApiResponse::success($dictionary, '更新成功');
        } catch (\Exception $e) {
            // 更新失败返回错误消息
            return ApiResponse::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除字典项
     *
     * @param int $id 字典项ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，失败时返回错误消息
     */
    public function destroy($id)
    {
        // 根据ID查询字典项
        $dictionary = DataDictionary::findOrFail($id);

        try {
            // 删除字典项记录
            $dictionary->delete();
            // 返回删除成功响应
            return ApiResponse::success(null, '删除成功');
        } catch (\Exception $e) {
            // 删除失败返回错误消息
            return ApiResponse::error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 根据类型获取字典列表
     *
     * @param string $type 字典类型
     * @return \Illuminate\Http\JsonResponse 返回指定类型下所有激活的字典项列表
     */
    public function getByType($type)
    {
        // 查询指定类型下所有激活的字典项
        $dictionaries = DataDictionary::where('type', $type)
            ->where('is_active', true)  // 只查询激活状态的
            ->orderBy('sort')           // 按排序字段排序
            ->orderBy('id')
            ->get();

        // 返回字典项列表
        return ApiResponse::success($dictionaries);
    }

    /**
     * 获取所有类型列表
     *
     * @return \Illuminate\Http\JsonResponse 返回所有不重复的字典类型列表
     */
    public function getTypes()
    {
        // 查询所有不重复的字典类型
        $types = DataDictionary::select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        // 返回类型列表
        return ApiResponse::success($types);
    }
}


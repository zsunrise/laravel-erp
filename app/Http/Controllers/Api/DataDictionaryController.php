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
     * @param Request $request 请求对象，支持 type（类型）、code（编码）、label（标签）、is_active（是否激活）和 group_by_type（按类型分组）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的字典列表或按类型分组的字典数据
     */
    public function index(Request $request)
    {
        $query = DataDictionary::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        if ($request->has('label')) {
            $query->where('label', 'like', '%' . $request->label . '%');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 如果请求按类型分组
        if ($request->has('group_by_type') && $request->group_by_type) {
            $dictionaries = $query->orderBy('type')->orderBy('sort')->get();
            $grouped = $dictionaries->groupBy('type');
            return ApiResponse::success($grouped);
        }

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
     * @param Request $request 请求对象，包含字典项信息（类型、编码、标签、值等）
     * @return \Illuminate\Http\JsonResponse 返回创建的字典项信息，状态码 201，失败时返回错误消息
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'code' => 'required|string|max:50',
            'label' => 'required|string|max:100',
            'value' => 'required|string|max:255',
            'sort' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        // 检查同一类型下code是否唯一
        $exists = DataDictionary::where('type', $validated['type'])
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return ApiResponse::error('该类型下编码已存在');
        }

        try {
            $dictionary = DataDictionary::create([
                'type' => $validated['type'],
                'code' => $validated['code'],
                'label' => $validated['label'],
                'value' => $validated['value'],
                'sort' => $validated['sort'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
                'description' => $validated['description'] ?? null,
            ]);

            return ApiResponse::success($dictionary, '创建成功', 201);
        } catch (\Exception $e) {
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
        $dictionary = DataDictionary::findOrFail($id);
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
        $dictionary = DataDictionary::findOrFail($id);

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

            $exists = DataDictionary::where('type', $type)
                ->where('code', $code)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return ApiResponse::error('该类型下编码已存在');
            }
        }

        try {
            $dictionary->update($validated);
            return ApiResponse::success($dictionary, '更新成功');
        } catch (\Exception $e) {
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
        $dictionary = DataDictionary::findOrFail($id);

        try {
            $dictionary->delete();
            return ApiResponse::success(null, '删除成功');
        } catch (\Exception $e) {
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
        $dictionaries = DataDictionary::where('type', $type)
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return ApiResponse::success($dictionaries);
    }

    /**
     * 获取所有类型列表
     *
     * @return \Illuminate\Http\JsonResponse 返回所有不重复的字典类型列表
     */
    public function getTypes()
    {
        $types = DataDictionary::select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        return ApiResponse::success($types);
    }
}


<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\DataDictionary;
use Illuminate\Http\Request;

class DataDictionaryController extends Controller
{
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

    public function show($id)
    {
        $dictionary = DataDictionary::findOrFail($id);
        return ApiResponse::success($dictionary);
    }

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


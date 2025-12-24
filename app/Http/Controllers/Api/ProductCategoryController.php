<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    /**
     * 获取产品分类列表
     *
     * @param Request $request 请求对象，支持 parent_id（父分类ID）、is_active（是否激活）和 tree（树形结构）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的分类列表或树形结构，包含父分类信息
     */
    public function index(Request $request)
    {
        $query = ProductCategory::with(['parent']);

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('tree')) {
            $categories = ProductCategory::whereNull('parent_id')
                ->with('children')
                ->orderBy('sort')
                ->get();
            return response()->json($categories);
        }

        return response()->json($query->orderBy('sort')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建新产品分类
     *
     * @param Request $request 请求对象，包含分类信息（名称、编码、父分类ID等）
     * @return \Illuminate\Http\JsonResponse 返回创建的分类信息，状态码 201
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:product_categories,code',
            'image' => 'nullable|string',
            'sort' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        $category = ProductCategory::create($validated);

        return response()->json($category->load('parent'), 201);
    }

    /**
     * 获取指定分类详情
     *
     * @param int $id 分类ID
     * @return \Illuminate\Http\JsonResponse 返回分类详细信息，包含父分类和子分类信息
     */
    public function show($id)
    {
        $category = ProductCategory::with(['parent', 'children'])->findOrFail($id);
        return ApiResponse::success($category, '获取成功');
    }

    /**
     * 更新分类信息
     *
     * @param Request $request 请求对象，包含要更新的分类字段
     * @param int $id 分类ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的分类信息
     */
    public function update(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:product_categories,id|not_in:' . $id,
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:product_categories,code,' . $id,
            'image' => 'nullable|string',
            'sort' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        $category->update($validated);

        return response()->json($category->load('parent'));
    }

    /**
     * 删除分类
     *
     * @param int $id 分类ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，如果分类下有子分类或商品则返回错误消息
     */
    public function destroy($id)
    {
        $category = ProductCategory::findOrFail($id);

        if ($category->children()->count() > 0) {
            return response()->json(['message' => '该分类下有子分类，无法删除'], 400);
        }

        if ($category->products()->count() > 0) {
            return response()->json(['message' => '该分类下有商品，无法删除'], 400);
        }

        $category->delete();

        return response()->json(['message' => '分类删除成功']);
    }
}

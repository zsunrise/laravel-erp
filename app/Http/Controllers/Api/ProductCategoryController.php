<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
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

    public function show($id)
    {
        $category = ProductCategory::with(['parent', 'children'])->findOrFail($id);
        return ApiResponse::success($category, '获取成功');
    }

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

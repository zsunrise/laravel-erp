<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * 获取产品列表
     *
     * @param Request $request 请求对象，支持 search（搜索关键词）、category_id（分类ID）和 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的产品列表，包含分类和单位信息
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'unit']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建新产品
     *
     * @param Request $request 请求对象，包含产品信息（名称、SKU、价格等）
     * @return \Illuminate\Http\JsonResponse 返回创建的产品信息，状态码 201
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products',
            'barcode' => 'nullable|string|max:255|unique:products',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'unit_id' => 'nullable|exists:units,id',
            'purchase_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $product = Product::create($validated);

        return response()->json($product->load(['category', 'unit']), 201);
    }

    /**
     * 获取指定产品详情
     *
     * @param int $id 产品ID
     * @return \Illuminate\Http\JsonResponse 返回产品详细信息，包含分类和单位信息
     */
    public function show($id)
    {
        $product = Product::with(['category', 'unit'])->findOrFail($id);
        return ApiResponse::success($product, '获取成功');
    }

    /**
     * 更新产品信息
     *
     * @param Request $request 请求对象，包含要更新的产品字段
     * @param int $id 产品ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的产品信息
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'nullable|exists:product_categories,id',
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'sometimes|required|string|max:255|unique:products,sku,' . $id,
            'barcode' => 'nullable|string|max:255|unique:products,barcode,' . $id,
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'unit_id' => 'nullable|exists:units,id',
            'purchase_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $product->update($validated);

        return response()->json($product->load(['category', 'unit']));
    }

    /**
     * 删除产品
     *
     * @param int $id 产品ID
     * @return \Illuminate\Http\JsonResponse 返回删除成功消息
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}

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
        // 构建查询，预加载分类和单位信息
        $query = Product::with(['category', 'unit']);

        // 关键词搜索：按名称、SKU、条码模糊匹配
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // 按产品分类筛选
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 返回分页结果
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
        // 验证产品信息参数
        $validated = $request->validate([
            'category_id' => 'nullable|exists:product_categories,id', // 产品分类
            'name' => 'required|string|max:255',                      // 产品名称（必填）
            'sku' => 'required|string|max:255|unique:products',       // SKU编码（唯一）
            'barcode' => 'nullable|string|max:255|unique:products',   // 条形码（唯一）
            'description' => 'nullable|string',                       // 产品描述
            'image' => 'nullable|string',                             // 产品图片
            'unit_id' => 'nullable|exists:units,id',                  // 计量单位
            'purchase_price' => 'nullable|numeric|min:0',             // 采购价
            'sale_price' => 'nullable|numeric|min:0',                 // 销售价
            'cost_price' => 'nullable|numeric|min:0',                 // 成本价
            'min_stock' => 'nullable|integer|min:0',                  // 最低库存
            'max_stock' => 'nullable|integer|min:0',                  // 最高库存
            'is_active' => 'sometimes|boolean',                       // 是否激活
        ]);

        // 创建产品记录
        $product = Product::create($validated);

        // 返回新建产品信息（包含分类和单位）
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
        // 根据ID查询产品，预加载分类和单位，找不到则抛出404
        $product = Product::with(['category', 'unit'])->findOrFail($id);
        // 返回标准化成功响应
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
        // 根据ID查询产品，找不到则抛出404
        $product = Product::findOrFail($id);

        // 验证更新参数（SKU和条码唯一性排除当前记录）
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

        // 更新产品信息
        $product->update($validated);

        // 返回更新后的产品信息
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
        // 根据ID查询产品，找不到则抛出404
        $product = Product::findOrFail($id);
        // 删除产品记录
        $product->delete();

        // 返回删除成功消息
        return response()->json(['message' => 'Product deleted successfully']);
    }
}

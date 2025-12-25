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
     * @queryParam parent_id integer 父分类ID Example: 1
     * @queryParam is_active boolean 是否激活（1:激活, 0:未激活） Example: 1
     * @queryParam tree boolean 是否返回树形结构（1:是, 0:否） Example: 1
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 parent_id（父分类ID）、is_active（是否激活）和 tree（树形结构）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的分类列表或树形结构，包含父分类信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载父分类信息
        $query = ProductCategory::with(['parent']);

        // 按父分类ID筛选
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 如果请求树形结构，返回过殾后的树形数据
        if ($request->has('tree')) {
            $categories = ProductCategory::whereNull('parent_id')
                ->with(['children' => function ($query) {
                    $query->orderBy('sort');
                }, 'children.children' => function ($query) {
                    $query->orderBy('sort');
                }])
                ->orderBy('sort')        // 按排序字段排序
                ->get();
            return response()->json($categories);
        }

        // 返回分页结果
        return response()->json($query->orderBy('sort')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建新产品分类
     *
     * @bodyParam parent_id integer 父分类ID Example: 1
     * @bodyParam name string required 分类名称 Example: 电子产品
     * @bodyParam code string required 分类编码（唯一） Example: ELEC001
     * @bodyParam image string 分类图片 Example: https://example.com/image.jpg
     * @bodyParam sort integer 排序值 Example: 1
     * @bodyParam is_active boolean 是否激活 Example: true
     * @bodyParam description string 描述 Example: 电子产品分类
     * @param Request $request 请求对象，包含分类信息（名称、编码、父分类ID等）
     * @return \Illuminate\Http\JsonResponse 返回创建的分类信息，状态码 201
     */
    public function store(Request $request)
    {
        // 验证分类信息参数
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:product_categories,id',                   // 父分类ID
            'name' => 'required|string|max:255',                                      // 分类名称（必填）
            'code' => 'required|string|max:255|unique:product_categories,code',       // 分类编码（唯一）
            'image' => 'nullable|string',                                             // 分类图片
            'sort' => 'nullable|integer|min:0',                                       // 排序值
            'is_active' => 'sometimes|boolean',                                       // 是否激活
            'description' => 'nullable|string',                                       // 描述
        ]);

        // 创建分类记录
        $category = ProductCategory::create($validated);

        // 返回新建分类信息（包含父分类）
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
        // 根据ID查询分类，预加载父分类和子分类信息，找不到则抛出404
        $category = ProductCategory::with(['parent', 'children'])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($category, '获取成功');
    }

    /**
     * 更新分类信息
     *
     * @bodyParam parent_id integer 父分类ID（不能是自己） Example: 1
     * @bodyParam name string 分类名称 Example: 电子产品
     * @bodyParam code string 分类编码（唯一，排除当前分类） Example: ELEC001
     * @bodyParam image string 分类图片 Example: https://example.com/image.jpg
     * @bodyParam sort integer 排序值 Example: 1
     * @bodyParam is_active boolean 是否激活 Example: true
     * @bodyParam description string 描述 Example: 电子产品分类
     * @param Request $request 请求对象，包含要更新的分类字段
     * @param int $id 分类ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的分类信息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询分类
        $category = ProductCategory::findOrFail($id);

        // 验证更新参数（父分类不能是自己）
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:product_categories,id|not_in:' . $id,
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:product_categories,code,' . $id,
            'image' => 'nullable|string',
            'sort' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        // 更新分类信息
        $category->update($validated);

        // 返回更新后的分类信息
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
        // 根据ID查询分类
        $category = ProductCategory::findOrFail($id);

        // 检查是否有子分类，有则不允许删除
        if ($category->children()->count() > 0) {
            return response()->json(['message' => '该分类下有子分类，无法删除'], 400);
        }

        // 检查是否有关联商品，有则不允许删除
        if ($category->products()->count() > 0) {
            return response()->json(['message' => '该分类下有商品，无法删除'], 400);
        }

        // 删除分类记录
        $category->delete();

        // 返回删除成功消息
        return response()->json(['message' => '分类删除成功']);
    }
}

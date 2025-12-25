<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Region;
use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * 获取区域列表
     *
     * @queryParam parent_id integer 父区域ID（不传则查询顶级区域） Example: 1
     * @queryParam level string 级别（省/市/区等） Example: 省
     * @queryParam is_active boolean 是否激活（1:激活, 0:未激活） Example: 1
     * @queryParam tree boolean 是否返回树形结构（1:是, 0:否） Example: 1
     * @queryParam per_page integer 每页数量 Example: 1000
     * @param Request $request 请求对象，支持 parent_id（父区域ID）、level（级别）、is_active（是否激活）和 tree（树形结构）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的区域列表或树形结构
     */
    public function index(Request $request)
    {
        // 构建查询
        $query = Region::query();

        // 按父区域ID筛选，无则查询顶级区域
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->whereNull('parent_id'); // 默认查询顶级区域
        }

        // 按级别筛选（省/市/区等）
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 如果请求树形结构，返回完整树形数据
        if ($request->has('tree')) {
            $regions = Region::whereNull('parent_id')
                ->with(['children' => function ($query) {
                    $query->orderBy('sort');
                }, 'children.children' => function ($query) {
                    $query->orderBy('sort');
                }])
                ->orderBy('sort')
                ->get();
            return response()->json($regions);
        }

        // 返回激活状态的区域列表
        return response()->json($query->where('is_active', true)->orderBy('sort')->paginate($request->get('per_page', 1000)));
    }

    /**
     * 创建新地区
     *
     * @bodyParam parent_id integer 父地区ID Example: 1
     * @bodyParam name string required 地区名称 Example: 北京市
     * @bodyParam code string required 地区编码（唯一） Example: BJ001
     * @bodyParam level integer required 级别（1-省/直辖市，2-市，3-区/县） Example: 1
     * @bodyParam sort integer 排序值 Example: 1
     * @bodyParam is_active boolean 是否激活 Example: true
     * @param Request $request 请求对象，包含地区信息（名称、编码、父地区ID等）
     * @return \Illuminate\Http\JsonResponse 返回创建的地区信息，状态码 201
     */
    public function store(Request $request)
    {
        // 验证地区信息参数
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:regions,id',                   // 父地区ID
            'name' => 'required|string|max:255',                           // 地区名称（必填）
            'code' => 'required|string|max:255|unique:regions,code',      // 地区编码（唯一）
            'level' => 'required|integer|in:1,2,3',                       // 级别（必填，1-3）
            'sort' => 'nullable|integer|min:0',                            // 排序值
            'is_active' => 'sometimes|boolean',                          // 是否激活
        ]);

        // 创建地区记录
        $region = Region::create($validated);

        // 返回新建地区信息（包含父地区）
        return response()->json($region->load('parent'), 201);
    }

    /**
     * 获取指定区域详情
     *
     * @param int $id 区域ID
     * @return \Illuminate\Http\JsonResponse 返回区域详细信息，包含父区域和子区域信息
     */
    public function show($id)
    {
        // 根据ID查询区域，预加载父区域和子区域信息，找不到则抛出404
        $region = Region::with(['parent', 'children'])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($region, '获取成功');
    }

    /**
     * 更新地区信息
     *
     * @bodyParam parent_id integer 父地区ID（不能是自己） Example: 1
     * @bodyParam name string 地区名称 Example: 北京市
     * @bodyParam code string 地区编码（唯一，排除当前地区） Example: BJ001
     * @bodyParam level integer 级别（1-省/直辖市，2-市，3-区/县） Example: 1
     * @bodyParam sort integer 排序值 Example: 1
     * @bodyParam is_active boolean 是否激活 Example: true
     * @param Request $request 请求对象，包含要更新的地区字段
     * @param int $id 地区ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的地区信息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询地区
        $region = Region::findOrFail($id);

        // 验证更新参数（父地区不能是自己）
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:regions,id|not_in:' . $id,
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:regions,code,' . $id,
            'level' => 'sometimes|required|integer|in:1,2,3',
            'sort' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        // 更新地区信息
        $region->update($validated);

        // 返回更新后的地区信息
        return response()->json($region->load('parent'));
    }

    /**
     * 删除地区
     *
     * @param int $id 地区ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，如果地区下有子地区或关联数据则返回错误消息
     */
    public function destroy($id)
    {
        // 根据ID查询地区
        $region = Region::findOrFail($id);

        // 检查是否有子地区，有则不允许删除
        if ($region->children()->count() > 0) {
            return response()->json(['message' => '该地区下有子地区，无法删除'], 400);
        }

        // 检查是否有关联的仓库，有则不允许删除
        if (Warehouse::where('region_id', $id)->count() > 0) {
            return response()->json(['message' => '该地区下有关联的仓库，无法删除'], 400);
        }

        // 检查是否有关联的客户，有则不允许删除
        if (Customer::where('region_id', $id)->count() > 0) {
            return response()->json(['message' => '该地区下有关联的客户，无法删除'], 400);
        }

        // 检查是否有关联的供应商，有则不允许删除
        if (Supplier::where('region_id', $id)->count() > 0) {
            return response()->json(['message' => '该地区下有关联的供应商，无法删除'], 400);
        }

        // 删除地区记录
        $region->delete();

        // 返回删除成功消息
        return response()->json(['message' => '地区删除成功']);
    }
}


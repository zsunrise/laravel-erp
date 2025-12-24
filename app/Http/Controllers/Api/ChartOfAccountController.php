<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    /**
     * 获取会计科目列表
     *
     * @queryParam type string 科目类型（asset/liability/equity/revenue/expense） Example: asset
     * @queryParam category string 科目分类 Example: 流动资产
     * @queryParam is_active boolean 是否激活（1:激活, 0:未激活） Example: 1
     * @queryParam parent_id integer 父科目ID Example: 1
     * @queryParam tree boolean 是否返回树形结构（1:是, 0:否） Example: 1
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 type（类型）、category（分类）、is_active（是否激活）、parent_id（父科目ID）和 tree（树形结构）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的科目列表或树形结构，包含父科目信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载父科目信息
        $query = ChartOfAccount::with('parent');

        // 按科目类型筛选
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // 按科目分类筛选
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 按父科目筛选
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // 如果请求树形结构，返回层级数据
        if ($request->has('tree')) {
            $accounts = ChartOfAccount::whereNull('parent_id')
                ->with('children')  // 递归加载子科目
                ->orderBy('order')
                ->get();
            return response()->json($accounts);
        }

        // 按排序字段排列，返回分页结果
        return response()->json($query->orderBy('order')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建会计科目
     *
     * @bodyParam code string required 科目编码（唯一） Example: 1001
     * @bodyParam name string required 科目名称 Example: 库存现金
     * @bodyParam parent_id integer 父科目ID Example: 1
     * @bodyParam type string required 科目类型（asset/liability/equity/revenue/expense） Example: asset
     * @bodyParam category string required 科目分类 Example: 流动资产
     * @bodyParam is_detail boolean 是否明细科目 Example: true
     * @bodyParam level integer 科目层级（自动计算） Example: 1
     * @bodyParam order integer 排序顺序 Example: 1
     * @bodyParam is_active boolean 是否激活 Example: true
     * @bodyParam remark string 备注 Example: 主要科目
     * @param Request $request 请求对象，包含科目信息（编码、名称、类型、分类等）
     * @return \Illuminate\Http\JsonResponse 返回创建的科目信息，状态码 201
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:chart_of_accounts,code',
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'category' => 'required|string',
            'is_detail' => 'sometimes|boolean',
            'level' => 'sometimes|integer|min:1',
            'order' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'remark' => 'nullable|string',
        ]);

        // 自动计算科目层级
        if ($validated['parent_id']) {
            // 有父科目，层级为父科目层级+1
            $parent = ChartOfAccount::find($validated['parent_id']);
            $validated['level'] = $parent->level + 1;
        } else {
            // 无父科目，为一级科目
            $validated['level'] = 1;
        }

        // 创建科目记录
        $account = ChartOfAccount::create($validated);

        // 返回新建科目信息
        return response()->json($account->load('parent'), 201);
    }

    /**
     * 获取指定科目详情
     *
     * @param int $id 科目ID
     * @return \Illuminate\Http\JsonResponse 返回科目详细信息，包含父科目和子科目信息
     */
    public function show($id)
    {
        // 根据ID查询科目，预加载父科目和子科目信息
        $account = ChartOfAccount::with(['parent', 'children'])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($account, '获取成功');
    }

    /**
     * 更新会计科目
     *
     * @bodyParam code string 科目编码（唯一，排除当前科目） Example: 1001
     * @bodyParam name string 科目名称 Example: 库存现金
     * @bodyParam parent_id integer 父科目ID（不能是自己） Example: 1
     * @bodyParam type string 科目类型（asset/liability/equity/revenue/expense） Example: asset
     * @bodyParam category string 科目分类 Example: 流动资产
     * @bodyParam is_detail boolean 是否明细科目 Example: true
     * @bodyParam order integer 排序顺序 Example: 1
     * @bodyParam is_active boolean 是否激活 Example: true
     * @bodyParam remark string 备注 Example: 主要科目
     * @param Request $request 请求对象，包含要更新的科目字段
     * @param int $id 科目ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的科目信息
     */
    public function update(Request $request, $id)
    {
        $account = ChartOfAccount::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|required|string|unique:chart_of_accounts,code,' . $id,
            'name' => 'sometimes|required|string|max:255',
            'parent_id' => 'nullable|exists:chart_of_accounts,id|not_in:' . $id,
            'type' => 'sometimes|required|in:asset,liability,equity,revenue,expense',
            'category' => 'sometimes|required|string',
            'is_detail' => 'sometimes|boolean',
            'order' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'remark' => 'nullable|string',
        ]);

        // 更新科目信息
        $account->update($validated);

        // 返回更新后的科目信息
        return response()->json($account->load('parent'));
    }

    /**
     * 删除会计科目
     *
     * @param int $id 科目ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，如果科目下有子科目或账务记录则返回错误消息
     */
    public function destroy($id)
    {
        // 根据ID查询科目
        $account = ChartOfAccount::findOrFail($id);

        // 检查是否有子科目
        if ($account->children()->count() > 0) {
            return response()->json(['message' => '该科目下有子科目，无法删除'], 400);
        }

        // 检查是否有总账分录记录
        if ($account->ledgerEntries()->count() > 0) {
            return response()->json(['message' => '该科目已有账务记录，无法删除'], 400);
        }

        // 删除科目记录
        $account->delete();

        // 返回删除成功消息
        return response()->json(['message' => '会计科目删除成功']);
    }
}

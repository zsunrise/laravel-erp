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
     * @param Request $request 请求对象，支持 type（类型）、category（分类）、is_active（是否激活）、parent_id（父科目ID）和 tree（树形结构）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的科目列表或树形结构，包含父科目信息
     */
    public function index(Request $request)
    {
        $query = ChartOfAccount::with('parent');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->has('tree')) {
            $accounts = ChartOfAccount::whereNull('parent_id')
                ->with('children')
                ->orderBy('order')
                ->get();
            return response()->json($accounts);
        }

        return response()->json($query->orderBy('order')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建会计科目
     *
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

        if ($validated['parent_id']) {
            $parent = ChartOfAccount::find($validated['parent_id']);
            $validated['level'] = $parent->level + 1;
        } else {
            $validated['level'] = 1;
        }

        $account = ChartOfAccount::create($validated);

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
        $account = ChartOfAccount::with(['parent', 'children'])->findOrFail($id);
        return ApiResponse::success($account, '获取成功');
    }

    /**
     * 更新会计科目
     *
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

        $account->update($validated);

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
        $account = ChartOfAccount::findOrFail($id);

        if ($account->children()->count() > 0) {
            return response()->json(['message' => '该科目下有子科目，无法删除'], 400);
        }

        if ($account->ledgerEntries()->count() > 0) {
            return response()->json(['message' => '该科目已有账务记录，无法删除'], 400);
        }

        $account->delete();

        return response()->json(['message' => '会计科目删除成功']);
    }
}

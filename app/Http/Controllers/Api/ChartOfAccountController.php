<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
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

    public function show($id)
    {
        $account = ChartOfAccount::with(['parent', 'children'])->findOrFail($id);
        return ApiResponse::success($account, '获取成功');
    }

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

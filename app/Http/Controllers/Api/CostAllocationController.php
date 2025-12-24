<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\CostAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CostAllocationController extends Controller
{
    public function index(Request $request)
    {
        $query = CostAllocation::with(['product', 'creator', 'approver']);

        if ($request->has('allocation_no')) {
            $query->where('allocation_no', 'like', '%' . $request->allocation_no . '%');
        }

        if ($request->has('cost_type')) {
            $query->where('cost_type', $request->cost_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->has('reference_id')) {
            $query->where('reference_id', $request->reference_id);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('start_date')) {
            $query->whereDate('allocation_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('allocation_date', '<=', $request->end_date);
        }

        return ApiResponse::success(
            $query->orderBy('allocation_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'allocation_date' => 'required|date',
            'cost_type' => 'required|in:material,labor,overhead',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'reference_no' => 'nullable|string',
            'product_id' => 'nullable|exists:products,id',
            'total_amount' => 'required|numeric|min:0',
            'allocation_method' => 'sometimes|in:direct,proportion,quantity',
            'remark' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // 生成分配单号
            $allocationNo = 'CA' . date('YmdHis') . rand(1000, 9999);
            while (CostAllocation::where('allocation_no', $allocationNo)->exists()) {
                $allocationNo = 'CA' . date('YmdHis') . rand(1000, 9999);
            }

            $costAllocation = CostAllocation::create([
                'allocation_no' => $allocationNo,
                'allocation_date' => $validated['allocation_date'],
                'cost_type' => $validated['cost_type'],
                'reference_type' => $validated['reference_type'] ?? null,
                'reference_id' => $validated['reference_id'] ?? null,
                'reference_no' => $validated['reference_no'] ?? null,
                'product_id' => $validated['product_id'] ?? null,
                'total_amount' => $validated['total_amount'],
                'allocated_amount' => 0,
                'allocation_method' => $validated['allocation_method'] ?? 'direct',
                'status' => 'draft',
                'created_by' => auth()->id(),
                'remark' => $validated['remark'] ?? null,
            ]);

            DB::commit();

            return ApiResponse::success($costAllocation->load(['product', 'creator']), '成本分配单创建成功', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('创建失败：' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $costAllocation = CostAllocation::with(['product', 'creator', 'approver'])
            ->findOrFail($id);

        return ApiResponse::success($costAllocation);
    }

    public function update(Request $request, $id)
    {
        $costAllocation = CostAllocation::findOrFail($id);

        if ($costAllocation->status !== 'draft') {
            return ApiResponse::error('只有草稿状态的成本分配单可以编辑');
        }

        $validated = $request->validate([
            'allocation_date' => 'sometimes|date',
            'cost_type' => 'sometimes|in:material,labor,overhead',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'reference_no' => 'nullable|string',
            'product_id' => 'nullable|exists:products,id',
            'total_amount' => 'sometimes|numeric|min:0',
            'allocation_method' => 'sometimes|in:direct,proportion,quantity',
            'remark' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $costAllocation->update($validated);

            DB::commit();

            return ApiResponse::success($costAllocation->load(['product', 'creator']), '更新成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('更新失败：' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $costAllocation = CostAllocation::findOrFail($id);

        if ($costAllocation->status !== 'draft') {
            return ApiResponse::error('只有草稿状态的成本分配单可以删除');
        }

        try {
            $costAllocation->delete();
            return ApiResponse::success(null, '删除成功');
        } catch (\Exception $e) {
            return ApiResponse::error('删除失败：' . $e->getMessage());
        }
    }

    public function approve($id)
    {
        $costAllocation = CostAllocation::findOrFail($id);

        if ($costAllocation->status !== 'draft') {
            return ApiResponse::error('只有草稿状态的成本分配单可以审核');
        }

        try {
            DB::beginTransaction();

            $costAllocation->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            return ApiResponse::success($costAllocation->load(['product', 'creator', 'approver']), '审核成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('审核失败：' . $e->getMessage());
        }
    }

    public function complete($id)
    {
        $costAllocation = CostAllocation::findOrFail($id);

        if ($costAllocation->status !== 'approved') {
            return ApiResponse::error('只有已审核状态的成本分配单可以完成');
        }

        try {
            DB::beginTransaction();

            $costAllocation->update([
                'status' => 'completed',
                'allocated_amount' => $costAllocation->total_amount,
            ]);

            DB::commit();

            return ApiResponse::success($costAllocation->load(['product', 'creator', 'approver']), '完成成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('完成失败：' . $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\CostAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CostAllocationController extends Controller
{
    /**
     * 获取成本分配单列表
     *
     * @queryParam allocation_no string 分配单号（模糊匹配） Example: CA20240115001
     * @queryParam cost_type string 成本类型（material/labor/overhead） Example: material
     * @queryParam status string 状态（draft/approved/completed/cancelled） Example: approved
     * @queryParam reference_type string 关联业务类型 Example: work_order
     * @queryParam reference_id integer 关联业务ID Example: 1
     * @queryParam product_id integer 产品ID Example: 1
     * @queryParam start_date date 开始日期 Example: 2024-01-01
     * @queryParam end_date date 结束日期 Example: 2024-12-31
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 allocation_no（分配单号）、cost_type（成本类型）、status（状态）、reference_type/reference_id（关联信息）、product_id（产品ID）和 start_date/end_date（日期范围）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的成本分配单列表，包含产品、创建人和审批人信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载产品、创建人和审批人信息
        $query = CostAllocation::with(['product', 'creator', 'approver']);

        // 按分配单号模糊搜索
        if ($request->has('allocation_no')) {
            $query->where('allocation_no', 'like', '%' . $request->allocation_no . '%');
        }

        // 按成本类型筛选（material/labor/overhead）
        if ($request->has('cost_type')) {
            $query->where('cost_type', $request->cost_type);
        }

        // 按状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 按关联业务类型筛选
        if ($request->has('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        // 按关联业务ID筛选
        if ($request->has('reference_id')) {
            $query->where('reference_id', $request->reference_id);
        }

        // 按产品筛选
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // 按日期范围筛选：开始日期
        if ($request->has('start_date')) {
            $query->whereDate('allocation_date', '>=', $request->start_date);
        }

        // 按日期范围筛选：结束日期
        if ($request->has('end_date')) {
            $query->whereDate('allocation_date', '<=', $request->end_date);
        }

        // 按分配日期和创建时间倒序排列，返回分页结果
        return ApiResponse::success(
            $query->orderBy('allocation_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15))
        );
    }

    /**
     * 创建成本分配单
     *
     * @bodyParam allocation_date date required 分配日期 Example: 2024-01-15
     * @bodyParam cost_type string required 成本类型（material/labor/overhead） Example: material
     * @bodyParam reference_type string 关联业务类型 Example: work_order
     * @bodyParam reference_id integer 关联业务ID Example: 1
     * @bodyParam reference_no string 关联业务编号 Example: WO001
     * @bodyParam product_id integer 产品ID Example: 1
     * @bodyParam total_amount number required 总金额（最小0） Example: 5000
     * @bodyParam allocation_method string 分配方法（direct/proportion/quantity） Example: direct
     * @bodyParam remark string 备注 Example: 材料成本分配
     * @param Request $request 请求对象，包含成本分配单信息（分配日期、成本类型、总金额、分配方法等）
     * @return \Illuminate\Http\JsonResponse 返回创建的成本分配单信息，状态码 201，失败时返回错误消息
     */
    public function store(Request $request)
    {
        // 验证成本分配单参数
        $validated = $request->validate([
            'allocation_date' => 'required|date',                      // 分配日期
            'cost_type' => 'required|in:material,labor,overhead',      // 成本类型
            'reference_type' => 'nullable|string',                     // 关联业务类型
            'reference_id' => 'nullable|integer',                      // 关联业务ID
            'reference_no' => 'nullable|string',                       // 关联业务编号
            'product_id' => 'nullable|exists:products,id',             // 产品ID
            'total_amount' => 'required|numeric|min:0',                // 总金额
            'allocation_method' => 'sometimes|in:direct,proportion,quantity', // 分配方法
            'remark' => 'nullable|string',                             // 备注
        ]);

        try {
            DB::beginTransaction();

            // 生成分配单号（CA + 日期时间 + 随机数）
            $allocationNo = 'CA' . date('YmdHis') . rand(1000, 9999);
            // 检查并避免单号重复
            while (CostAllocation::where('allocation_no', $allocationNo)->exists()) {
                $allocationNo = 'CA' . date('YmdHis') . rand(1000, 9999);
            }

            // 创建成本分配单
            $costAllocation = CostAllocation::create([
                'allocation_no' => $allocationNo,
                'allocation_date' => $validated['allocation_date'],
                'cost_type' => $validated['cost_type'],
                'reference_type' => $validated['reference_type'] ?? null,
                'reference_id' => $validated['reference_id'] ?? null,
                'reference_no' => $validated['reference_no'] ?? null,
                'product_id' => $validated['product_id'] ?? null,
                'total_amount' => $validated['total_amount'],
                'allocated_amount' => 0,  // 已分配金额初始为0
                'allocation_method' => $validated['allocation_method'] ?? 'direct',
                'status' => 'draft',  // 初始状态为草稿
                'created_by' => auth()->id(),
                'remark' => $validated['remark'] ?? null,
            ]);

            DB::commit();

            // 返回创建成功响应
            return ApiResponse::success($costAllocation->load(['product', 'creator']), '成本分配单创建成功', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('创建失败：' . $e->getMessage());
        }
    }

    /**
     * 获取指定成本分配单详情
     *
     * @param int $id 成本分配单ID
     * @return \Illuminate\Http\JsonResponse 返回成本分配单详细信息，包含产品、创建人和审批人信息
     */
    public function show($id)
    {
        // 根据ID查询成本分配单，预加载关联数据
        $costAllocation = CostAllocation::with(['product', 'creator', 'approver'])
            ->findOrFail($id);

        // 返回标准化成功响应
        return ApiResponse::success($costAllocation);
    }

    /**
     * 更新成本分配单
     *
     * @param Request $request 请求对象，包含要更新的成本分配单字段
     * @param int $id 成本分配单ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的成本分配单信息，只能修改草稿状态的分配单，失败时返回错误消息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询成本分配单
        $costAllocation = CostAllocation::findOrFail($id);

        // 检查状态：只能修改草稿状态的分配单
        if ($costAllocation->status !== 'draft') {
            return ApiResponse::error('只有草稿状态的成本分配单可以编辑');
        }

        // 验证更新参数
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

            // 更新分配单信息
            $costAllocation->update($validated);

            DB::commit();

            // 返回更新成功响应
            return ApiResponse::success($costAllocation->load(['product', 'creator']), '更新成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除成本分配单
     *
     * @param int $id 成本分配单ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，只能删除草稿状态的分配单，失败时返回错误消息
     */
    public function destroy($id)
    {
        // 根据ID查询成本分配单
        $costAllocation = CostAllocation::findOrFail($id);

        // 检查状态：只能删除草稿状态的分配单
        if ($costAllocation->status !== 'draft') {
            return ApiResponse::error('只有草稿状态的成本分配单可以删除');
        }

        try {
            // 删除分配单记录
            $costAllocation->delete();
            return ApiResponse::success(null, '删除成功');
        } catch (\Exception $e) {
            return ApiResponse::error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 审批成本分配单
     *
     * @param int $id 成本分配单ID
     * @return \Illuminate\Http\JsonResponse 返回审批后的成本分配单信息，只能审批草稿状态的分配单，失败时返回错误消息
     */
    public function approve($id)
    {
        // 根据ID查询成本分配单
        $costAllocation = CostAllocation::findOrFail($id);

        // 检查状态：只能审批草稿状态的分配单
        if ($costAllocation->status !== 'draft') {
            return ApiResponse::error('只有草稿状态的成本分配单可以审核');
        }

        try {
            DB::beginTransaction();

            // 更新审批信息
            $costAllocation->update([
                'status' => 'approved',           // 更新状态为已审批
                'approved_by' => auth()->id(),    // 记录审批人
                'approved_at' => now(),           // 记录审批时间
            ]);

            DB::commit();

            // 返回审批成功响应
            return ApiResponse::success($costAllocation->load(['product', 'creator', 'approver']), '审核成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('审核失败：' . $e->getMessage());
        }
    }

    /**
     * 完成成本分配单
     *
     * @param int $id 成本分配单ID
     * @return \Illuminate\Http\JsonResponse 返回完成后的成本分配单信息，只能完成已审核状态的分配单，失败时返回错误消息
     */
    public function complete($id)
    {
        // 根据ID查询成本分配单
        $costAllocation = CostAllocation::findOrFail($id);

        // 检查状态：只能完成已审批状态的分配单
        if ($costAllocation->status !== 'approved') {
            return ApiResponse::error('只有已审核状态的成本分配单可以完成');
        }

        try {
            DB::beginTransaction();

            // 更新完成信息
            $costAllocation->update([
                'status' => 'completed',                          // 状态更新为已完成
                'allocated_amount' => $costAllocation->total_amount, // 已分配金额等于总金额
            ]);

            DB::commit();

            // 返回完成成功响应
            return ApiResponse::success($costAllocation->load(['product', 'creator', 'approver']), '完成成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('完成失败：' . $e->getMessage());
        }
    }
}

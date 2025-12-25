<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\WorkOrder;
use App\Models\ProductionMaterialIssue;
use App\Models\ProductionReport;
use App\Services\ProductionService;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    protected $productionService;

    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    /**
     * 获取工单列表
     *
     * @queryParam product_id integer 产品ID Example: 1
     * @queryParam warehouse_id integer 仓库ID Example: 1
     * @queryParam status string 工单状态（draft/approved/in_progress/completed/cancelled） Example: approved
     * @queryParam assigned_to integer 分配人ID Example: 1
     * @queryParam start_date date 开始日期 Example: 2024-01-01
     * @queryParam end_date date 结束日期 Example: 2024-12-31
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 product_id（产品ID）、warehouse_id（仓库ID）、status（状态）、assigned_to（分配人ID）和 start_date/end_date（日期范围）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的工单列表，包含产品、仓库、分配人和创建人信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载产品、仓库、分配人和创建人信息
        $query = WorkOrder::with(['product', 'warehouse', 'assignedTo', 'creator']);

        // 按产品ID筛选
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // 按仓库ID筛选
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 按工单状态筛选（draft/approved/in_progress/completed/cancelled）
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 按分配人筛选
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // 按开始日期筛选
        if ($request->has('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        // 按结束日期筛选
        if ($request->has('end_date')) {
            $query->whereDate('start_date', '<=', $request->end_date);
        }

        // 按开始日期倒序排列，返回分页结果
        return response()->json($query->orderBy('start_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建工单
     *
     * @bodyParam production_plan_id integer 生产计划ID Example: 1
     * @bodyParam production_plan_item_id integer 生产计划明细ID Example: 1
     * @bodyParam product_id integer required 产品ID Example: 1
     * @bodyParam bom_id integer BOM ID Example: 1
     * @bodyParam process_route_id integer 工艺路线ID Example: 1
     * @bodyParam warehouse_id integer required 仓库ID Example: 1
     * @bodyParam quantity integer required 生产数量（最小1） Example: 100
     * @bodyParam start_date date required 开始日期 Example: 2024-01-15
     * @bodyParam planned_end_date date required 计划结束日期（必须晚于开始日期） Example: 2024-01-20
     * @bodyParam assigned_to integer 分配人ID Example: 1
     * @bodyParam remark string 备注 Example: 紧急工单
     * @param Request $request 请求对象，包含工单信息（产品ID、BOM、工艺路线、数量等）
     * @return \Illuminate\Http\JsonResponse 返回创建的工单信息，状态码 201，失败时返回错误消息
     */
    public function store(Request $request)
    {
        // 验证工单参数
        $validated = $request->validate([
            'production_plan_id' => 'nullable|exists:production_plans,id',       // 生产计划ID
            'production_plan_item_id' => 'nullable|exists:production_plan_items,id', // 生产计划明细ID
            'product_id' => 'required|exists:products,id',                       // 产品ID（必填）
            'bom_id' => 'nullable|exists:boms,id',                               // BOM ID
            'process_route_id' => 'nullable|exists:process_routes,id',           // 工艺路线ID
            'warehouse_id' => 'required|exists:warehouses,id',                   // 仓库ID（必填）
            'quantity' => 'required|integer|min:1',                              // 生产数量（必填）
            'start_date' => 'required|date',                                     // 开始日期（必填）
            'planned_end_date' => 'required|date|after:start_date',              // 计划结束日期
            'assigned_to' => 'nullable|exists:users,id',                         // 分配人
            'remark' => 'nullable|string',                                       // 备注
        ]);

        try {
            // 调用服务层创建工单，自动生成工单号并创建工序明细
            $workOrder = $this->productionService->createWorkOrder($validated);
            // 创建成功返回 201 状态码
            return response()->json($workOrder, 201);
        } catch (\Exception $e) {
            // 创建失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取指定工单详情
     *
     * @param int $id 工单ID
     * @return \Illuminate\Http\JsonResponse 返回工单详细信息，包含产品、BOM、工艺路线、仓库、明细项、领料单和报工单信息
     */
    public function show($id)
    {
        // 根据ID查询工单，预加载全部关联信息
        $workOrder = WorkOrder::with([
            'product', 'bom', 'processRoute', 'warehouse', 'assignedTo', 'creator', 'approver',
            'items.processRouteStep', 'items.assignedTo',       // 工序明细
            'materialIssues.items.product', 'reports.reporter'  // 领料单和报工单
        ])->findOrFail($id); // 找不到则抛出404异常
        // 返回标准化成功响应
        return ApiResponse::success($workOrder, '获取成功');
    }

    /**
     * 更新工单信息
     *
     * @param Request $request 请求对象，包含要更新的工单字段
     * @param int $id 工单ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的工单信息，只能修改草稿状态的工单
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询工单
        $workOrder = WorkOrder::findOrFail($id);

        // 检查工单状态，只能修改草稿状态的工单
        if ($workOrder->status != 'draft') {
            return response()->json(['message' => '只能修改草稿状态的工单'], 400);
        }

        // 验证更新参数
        $validated = $request->validate([
            'product_id' => 'sometimes|required|exists:products,id',
            'bom_id' => 'nullable|exists:boms,id',
            'process_route_id' => 'nullable|exists:process_routes,id',
            'warehouse_id' => 'sometimes|required|exists:warehouses,id',
            'quantity' => 'sometimes|required|integer|min:1',
            'start_date' => 'sometimes|required|date',
            'planned_end_date' => 'sometimes|required|date|after:start_date',
            'assigned_to' => 'nullable|exists:users,id',
            'remark' => 'nullable|string',
        ]);

        // 更新工单信息
        $workOrder->update($validated);

        // 返回更新后的工单信息
        return response()->json($workOrder->load(['product', 'warehouse', 'items']));
    }

    /**
     * 删除工单
     *
     * @param int $id 工单ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，只能删除草稿状态的工单
     */
    public function destroy($id)
    {
        // 根据ID查询工单
        $workOrder = WorkOrder::findOrFail($id);

        // 检查工单状态，只能删除草稿状态的工单
        if ($workOrder->status != 'draft') {
            return response()->json(['message' => '只能删除草稿状态的工单'], 400);
        }

        // 删除工单记录
        $workOrder->delete();

        // 返回删除成功消息
        return response()->json(['message' => '工单删除成功']);
    }

    /**
     * 提交工单审核
     *
     * @param int $id 工单ID
     * @return \Illuminate\Http\JsonResponse 返回提交后的工单信息，失败时返回错误消息
     */
    public function submit($id)
    {
        try {
            // 调用服务层提交审核，将状态从 draft 转为 pending，并启动审批流程
            $workOrder = $this->productionService->submitWorkOrderForApproval($id);
            // 提交成功返回工单信息
            return response()->json($workOrder);
        } catch (\Exception $e) {
            // 提交失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 审批工单（直接审批，不通过工作流）
     *
     * @param int $id 工单ID
     * @return \Illuminate\Http\JsonResponse 返回审批后的工单信息，失败时返回错误消息
     */
    public function approve($id)
    {
        try {
            // 调用服务层审批工单，将状态从 draft/pending 更新为 approved
            $workOrder = $this->productionService->approveWorkOrder($id);
            // 审批成功返回工单信息
            return response()->json($workOrder);
        } catch (\Exception $e) {
            // 审批失败（如状态不正确）返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 工单领料
     *
     * @param int $id 工单ID
     * @param Request $request 请求对象，包含领料明细项数组
     * @return \Illuminate\Http\JsonResponse 返回创建的领料单信息，状态码 201，失败时返回错误消息
     */
    public function issueMaterial($id, Request $request)
    {
        // 验证领料明细参数
        $validated = $request->validate([
            'items' => 'required|array|min:1',                   // 领料明细（至少一条）
            'items.*.product_id' => 'required|exists:products,id', // 产品ID
            'items.*.quantity' => 'required|integer|min:1',      // 领料数量
            'items.*.location_id' => 'nullable|exists:warehouse_locations,id', // 库位ID
        ]);

        try {
            // 调用服务层执行领料，创建领料单并扣减库存
            $materialIssue = $this->productionService->issueMaterial($id, $validated['items']);
            // 领料成功返回 201 状态码
            return response()->json($materialIssue, 201);
        } catch (\Exception $e) {
            // 领料失败（如库存不足）返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 工单退料
     *
     * @param int $id 工单ID
     * @param Request $request 请求对象，包含退料明细项数组
     * @return \Illuminate\Http\JsonResponse 返回创建的退料单信息，状态码 201，失败时返回错误消息
     */
    public function returnMaterial($id, Request $request)
    {
        // 验证退料明细参数
        $validated = $request->validate([
            'items' => 'required|array|min:1',                   // 退料明细（至少一条）
            'items.*.product_id' => 'required|exists:products,id', // 产品ID
            'items.*.quantity' => 'required|integer|min:1',      // 退料数量
            'items.*.location_id' => 'nullable|exists:warehouse_locations,id', // 库位ID
        ]);

        try {
            // 调用服务层执行退料，创建退料单并加回库存
            $materialReturn = $this->productionService->returnMaterial($id, $validated['items']);
            // 退料成功返回 201 状态码
            return response()->json($materialReturn, 201);
        } catch (\Exception $e) {
            // 退料失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 完成工单
     *
     * @param int $id 工单ID
     * @return \Illuminate\Http\JsonResponse 返回完成后的工单信息，失败时返回错误消息
     */
    public function complete($id)
    {
        try {
            // 调用服务层完成工单，将状态更新为 completed，生成入库记录
            $workOrder = $this->productionService->completeWorkOrder($id);
            // 完成成功返回工单信息
            return response()->json($workOrder);
        } catch (\Exception $e) {
            // 完成失败（如工序未全部完成）返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取工单的领料单列表
     *
     * @param int $id 工单ID
     * @return \Illuminate\Http\JsonResponse 返回该工单的所有领料单列表
     */
    public function materialIssues($id)
    {
        // 验证工单存在
        $workOrder = WorkOrder::findOrFail($id);
        // 查询该工单的所有领料单，预加载明细、创建人、审批人
        $materialIssues = ProductionMaterialIssue::with(['items.product.unit', 'items.location', 'creator', 'approver'])
            ->where('work_order_id', $id)
            ->orderBy('issue_date', 'desc')  // 按领料日期倒序
            ->get();

        // 返回领料单列表
        return response()->json(['data' => $materialIssues]);
    }

    /**
     * 获取工单的报工单列表
     *
     * @param int $id 工单ID
     * @return \Illuminate\Http\JsonResponse 返回该工单的所有报工单列表
     */
    public function reports($id)
    {
        // 验证工单存在
        $workOrder = WorkOrder::findOrFail($id);
        // 查询该工单的所有报工单，预加载工序明细和报工人
        $reports = ProductionReport::with(['workOrderItem', 'reporter'])
            ->where('work_order_id', $id)
            ->orderBy('report_date', 'desc')  // 按报工日期倒序
            ->get();

        // 返回报工单列表
        return response()->json(['data' => $reports]);
    }
}

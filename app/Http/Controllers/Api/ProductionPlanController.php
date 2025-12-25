<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ProductionPlan;
use App\Services\ProductionService;
use Illuminate\Http\Request;

class ProductionPlanController extends Controller
{
    protected $productionService;

    public function __construct(ProductionService $productionService)
    {
        // 注入生产服务
        $this->productionService = $productionService;
    }

    /**
     * 获取生产计划列表
     *
     * @queryParam warehouse_id integer 仓库ID Example: 1
     * @queryParam status string 计划状态（draft/approved/in_progress/completed/cancelled） Example: approved
     * @queryParam start_date date 开始日期 Example: 2024-01-01
     * @queryParam end_date date 结束日期 Example: 2024-12-31
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 warehouse_id（仓库ID）、status（状态）和 start_date/end_date（日期范围）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的生产计划列表，包含仓库、销售订单和创建人信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载仓库、销售订单和创建人信息
        $query = ProductionPlan::with(['warehouse', 'salesOrder', 'creator']);

        // 按仓库筛选
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 按状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 按日期范围筛选：开始日期
        if ($request->has('start_date')) {
            $query->whereDate('plan_date', '>=', $request->start_date);
        }

        // 按日期范围筛选：结束日期
        if ($request->has('end_date')) {
            $query->whereDate('plan_date', '<=', $request->end_date);
        }

        // 按计划日期倒序排列，返回分页结果
        $paginator = $query->orderBy('plan_date', 'desc')->paginate($request->get('per_page', 15));
        
        // 为每个计划添加待审核标识
        $paginator->getCollection()->transform(function ($plan) {
            // 如果状态是草稿，检查是否有待审批的工作流实例
            if ($plan->status == \App\Constants\ProductionPlanStatus::DRAFT) {
                $hasPendingWorkflow = \App\Models\WorkflowInstance::where('reference_type', \App\Models\ProductionPlan::class)
                    ->where('reference_id', $plan->id)
                    ->where('status', \App\Constants\WorkflowStatus::PENDING)
                    ->exists();
                $plan->is_pending_approval = $hasPendingWorkflow;
            } else {
                $plan->is_pending_approval = false;
            }
            return $plan;
        });
        
        return response()->json($paginator);
    }

    /**
     * 创建生产计划
     *
     * @bodyParam sales_order_id integer 关联销售订单ID Example: 1
     * @bodyParam plan_date date required 计划日期 Example: 2024-01-15
     * @bodyParam start_date date required 开始日期 Example: 2024-01-15
     * @bodyParam end_date date required 结束日期（必须晚于开始日期） Example: 2024-01-20
     * @bodyParam warehouse_id integer required 仓库ID Example: 1
     * @bodyParam remark string 备注 Example: 紧急生产计划
     * @bodyParam items array required 明细项（至少一条） Example: 计划明细数组
     * @bodyParam items.*.product_id integer required 产品ID Example: 1
     * @bodyParam items.*.bom_id integer BOM ID Example: 1
     * @bodyParam items.*.process_route_id integer 工艺路线ID Example: 1
     * @bodyParam items.*.planned_quantity integer required 计划数量（最小1） Example: 100
     * @bodyParam items.*.planned_start_date date required 计划开始日期 Example: 2024-01-15
     * @bodyParam items.*.planned_end_date date required 计划结束日期（必须晚于计划开始日期） Example: 2024-01-20
     * @bodyParam items.*.priority integer 优先级 Example: 1
     * @bodyParam items.*.remark string 明细备注 Example: 优先生产
     * @param Request $request 请求对象，包含计划信息和明细项数组
     * @return \Illuminate\Http\JsonResponse 返回创建的计划信息，状态码 201，失败时返回错误消息
     */
    public function store(Request $request)
    {
        // 验证生产计划参数
        $validated = $request->validate([
            'sales_order_id' => 'nullable|exists:sales_orders,id',          // 关联销售订单
            'plan_date' => 'required|date',                                  // 计划日期（必填）
            'start_date' => 'required|date',                                 // 开始日期（必填）
            'end_date' => 'required|date|after:start_date',                  // 结束日期（必填）
            'warehouse_id' => 'required|exists:warehouses,id',               // 仓库（必填）
            'remark' => 'nullable|string',                                   // 备注
            'items' => 'required|array|min:1',                               // 明细项（必填）
            'items.*.product_id' => 'required|exists:products,id',           // 产品ID
            'items.*.bom_id' => 'nullable|exists:boms,id',                   // BOM ID
            'items.*.process_route_id' => 'nullable|exists:process_routes,id', // 工艺路线ID
            'items.*.planned_quantity' => 'required|integer|min:1',          // 计划数量
            'items.*.planned_start_date' => 'required|date',                 // 计划开始日期
            'items.*.planned_end_date' => 'required|date|after:items.*.planned_start_date', // 计划结束日期
            'items.*.priority' => 'nullable|integer|min:0',                  // 优先级
            'items.*.remark' => 'nullable|string',                           // 明细备注
        ]);

        try {
            // 调用生产服务创建计划
            $plan = $this->productionService->createPlan($validated);
            // 返回创建成功响应
            return response()->json($plan, 201);
        } catch (\Exception $e) {
            // 创建失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取指定生产计划详情
     *
     * @param int $id 生产计划ID
     * @return \Illuminate\Http\JsonResponse 返回计划详细信息，包含仓库、销售订单、创建人、审批人和明细项信息
     */
    public function show($id)
    {
        // 根据ID查询生产计划，预加载关联数据
        $plan = ProductionPlan::with(['warehouse', 'salesOrder', 'creator', 'approver', 'items.product', 'items.bom', 'items.processRoute'])
            ->findOrFail($id);
        
        // 检查是否有待审批的工作流实例
        if ($plan->status == \App\Constants\ProductionPlanStatus::DRAFT) {
            $plan->is_pending_approval = \App\Models\WorkflowInstance::where('reference_type', \App\Models\ProductionPlan::class)
                ->where('reference_id', $plan->id)
                ->where('status', \App\Constants\WorkflowStatus::PENDING)
                ->exists();
        } else {
            $plan->is_pending_approval = false;
        }
        
        // 返回标准化成功响应
        return ApiResponse::success($plan, '获取成功');
    }

    /**
     * 更新生产计划
     *
     * @param Request $request 请求对象，包含要更新的计划字段
     * @param int $id 生产计划ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的计划信息，只能修改草稿状态的计划
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询生产计划
        $plan = ProductionPlan::findOrFail($id);

        // 检查状态：只能修改草稿状态的计划
        if ($plan->status != 'draft') {
            return response()->json(['message' => '只能修改草稿状态的计划'], 400);
        }

        // 验证更新参数
        $validated = $request->validate([
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'plan_date' => 'sometimes|required|date',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'warehouse_id' => 'sometimes|required|exists:warehouses,id',
            'remark' => 'nullable|string',
        ]);

        // 更新计划信息
        $plan->update($validated);

        // 返回更新后的计划信息
        return response()->json($plan->load(['warehouse', 'salesOrder', 'items.product']));
    }

    /**
     * 删除生产计划
     *
     * @param int $id 生产计划ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，只能删除草稿状态的计划
     */
    public function destroy($id)
    {
        // 根据ID查询生产计划
        $plan = ProductionPlan::findOrFail($id);

        // 检查状态：只能删除草稿状态的计划
        if ($plan->status != 'draft') {
            return response()->json(['message' => '只能删除草稿状态的计划'], 400);
        }

        // 删除计划记录
        $plan->delete();

        // 返回删除成功消息
        return response()->json(['message' => '生产计划删除成功']);
    }

    /**
     * 提交生产计划审核
     *
     * @param int $id 生产计划ID
     * @return \Illuminate\Http\JsonResponse 返回提交后的计划信息，失败时返回错误消息
     */
    public function submit($id)
    {
        try {
            // 调用服务层提交审核，将状态从 draft 转为 pending，并启动审批流程
            $plan = $this->productionService->submitPlanForApproval($id);
            // 提交成功返回计划信息
            return response()->json($plan);
        } catch (\Exception $e) {
            // 提交失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 审批生产计划（直接审批，不通过工作流）
     *
     * @param int $id 生产计划ID
     * @return \Illuminate\Http\JsonResponse 返回审批后的计划信息，失败时返回错误消息
     */
    public function approve($id)
    {
        try {
            // 调用生产服务审批计划
            $plan = $this->productionService->approvePlan($id);
            // 返回审批后的计划信息
            return response()->json($plan);
        } catch (\Exception $e) {
            // 审批失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

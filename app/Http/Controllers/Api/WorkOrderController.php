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
     * @param Request $request 请求对象，支持 product_id（产品ID）、warehouse_id（仓库ID）、status（状态）、assigned_to（分配人ID）和 start_date/end_date（日期范围）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的工单列表，包含产品、仓库、分配人和创建人信息
     */
    public function index(Request $request)
    {
        $query = WorkOrder::with(['product', 'warehouse', 'assignedTo', 'creator']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('start_date', '<=', $request->end_date);
        }

        return response()->json($query->orderBy('start_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建工单
     *
     * @param Request $request 请求对象，包含工单信息（产品ID、BOM、工艺路线、数量等）
     * @return \Illuminate\Http\JsonResponse 返回创建的工单信息，状态码 201，失败时返回错误消息
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'production_plan_id' => 'nullable|exists:production_plans,id',
            'production_plan_item_id' => 'nullable|exists:production_plan_items,id',
            'product_id' => 'required|exists:products,id',
            'bom_id' => 'nullable|exists:boms,id',
            'process_route_id' => 'nullable|exists:process_routes,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'planned_end_date' => 'required|date|after:start_date',
            'assigned_to' => 'nullable|exists:users,id',
            'remark' => 'nullable|string',
        ]);

        try {
            $workOrder = $this->productionService->createWorkOrder($validated);
            return response()->json($workOrder, 201);
        } catch (\Exception $e) {
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
        $workOrder = WorkOrder::with([
            'product', 'bom', 'processRoute', 'warehouse', 'assignedTo', 'creator', 'approver',
            'items.processRouteStep', 'items.assignedTo',
            'materialIssues.items.product', 'reports.reporter'
        ])->findOrFail($id);
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
        $workOrder = WorkOrder::findOrFail($id);

        if ($workOrder->status != 'draft') {
            return response()->json(['message' => '只能修改草稿状态的工单'], 400);
        }

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

        $workOrder->update($validated);

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
        $workOrder = WorkOrder::findOrFail($id);

        if ($workOrder->status != 'draft') {
            return response()->json(['message' => '只能删除草稿状态的工单'], 400);
        }

        $workOrder->delete();

        return response()->json(['message' => '工单删除成功']);
    }

    /**
     * 审批工单
     *
     * @param int $id 工单ID
     * @return \Illuminate\Http\JsonResponse 返回审批后的工单信息，失败时返回错误消息
     */
    public function approve($id)
    {
        try {
            $workOrder = $this->productionService->approveWorkOrder($id);
            return response()->json($workOrder);
        } catch (\Exception $e) {
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
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.location_id' => 'nullable|exists:warehouse_locations,id',
        ]);

        try {
            $materialIssue = $this->productionService->issueMaterial($id, $validated['items']);
            return response()->json($materialIssue, 201);
        } catch (\Exception $e) {
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
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.location_id' => 'nullable|exists:warehouse_locations,id',
        ]);

        try {
            $materialReturn = $this->productionService->returnMaterial($id, $validated['items']);
            return response()->json($materialReturn, 201);
        } catch (\Exception $e) {
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
            $workOrder = $this->productionService->completeWorkOrder($id);
            return response()->json($workOrder);
        } catch (\Exception $e) {
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
        $workOrder = WorkOrder::findOrFail($id);
        $materialIssues = ProductionMaterialIssue::with(['items.product.unit', 'items.location', 'creator', 'approver'])
            ->where('work_order_id', $id)
            ->orderBy('issue_date', 'desc')
            ->get();

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
        $workOrder = WorkOrder::findOrFail($id);
        $reports = ProductionReport::with(['workOrderItem', 'reporter'])
            ->where('work_order_id', $id)
            ->orderBy('report_date', 'desc')
            ->get();

        return response()->json(['data' => $reports]);
    }
}

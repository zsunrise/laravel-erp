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
        $this->productionService = $productionService;
    }

    public function index(Request $request)
    {
        $query = ProductionPlan::with(['warehouse', 'salesOrder', 'creator']);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date')) {
            $query->whereDate('plan_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('plan_date', '<=', $request->end_date);
        }

        return response()->json($query->orderBy('plan_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'plan_date' => 'required|date',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'warehouse_id' => 'required|exists:warehouses,id',
            'remark' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.bom_id' => 'nullable|exists:boms,id',
            'items.*.process_route_id' => 'nullable|exists:process_routes,id',
            'items.*.planned_quantity' => 'required|integer|min:1',
            'items.*.planned_start_date' => 'required|date',
            'items.*.planned_end_date' => 'required|date|after:items.*.planned_start_date',
            'items.*.priority' => 'nullable|integer|min:0',
            'items.*.remark' => 'nullable|string',
        ]);

        try {
            $plan = $this->productionService->createPlan($validated);
            return response()->json($plan, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $plan = ProductionPlan::with(['warehouse', 'salesOrder', 'creator', 'approver', 'items.product', 'items.bom', 'items.processRoute'])
            ->findOrFail($id);
        return ApiResponse::success($plan, '获取成功');
    }

    public function update(Request $request, $id)
    {
        $plan = ProductionPlan::findOrFail($id);

        if ($plan->status != 'draft') {
            return response()->json(['message' => '只能修改草稿状态的计划'], 400);
        }

        $validated = $request->validate([
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'plan_date' => 'sometimes|required|date',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'warehouse_id' => 'sometimes|required|exists:warehouses,id',
            'remark' => 'nullable|string',
        ]);

        $plan->update($validated);

        return response()->json($plan->load(['warehouse', 'salesOrder', 'items.product']));
    }

    public function destroy($id)
    {
        $plan = ProductionPlan::findOrFail($id);

        if ($plan->status != 'draft') {
            return response()->json(['message' => '只能删除草稿状态的计划'], 400);
        }

        $plan->delete();

        return response()->json(['message' => '生产计划删除成功']);
    }

    public function approve($id)
    {
        try {
            $plan = $this->productionService->approvePlan($id);
            return response()->json($plan);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

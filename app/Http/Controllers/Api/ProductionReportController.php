<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ProductionReport;
use App\Models\WorkOrder;
use App\Services\ProductionService;
use Illuminate\Http\Request;

class ProductionReportController extends Controller
{
    protected $productionService;

    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    public function index(Request $request)
    {
        $query = ProductionReport::with(['workOrder', 'workOrderItem', 'reporter']);

        if ($request->has('work_order_id')) {
            $query->where('work_order_id', $request->work_order_id);
        }

        if ($request->has('start_date')) {
            $query->whereDate('report_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('report_date', '<=', $request->end_date);
        }

        return response()->json($query->orderBy('report_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'work_order_id' => 'required|exists:work_orders,id',
            'work_order_item_id' => 'nullable|exists:work_order_items,id',
            'report_date' => 'required|date',
            'quantity' => 'required|integer|min:1',
            'qualified_quantity' => 'nullable|integer|min:0',
            'defective_quantity' => 'nullable|integer|min:0',
            'work_hours' => 'nullable|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string',
        ]);

        try {
            $report = $this->productionService->reportWork($validated['work_order_id'], $validated);
            return response()->json($report, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $report = ProductionReport::with(['workOrder', 'workOrderItem', 'reporter'])
            ->findOrFail($id);
        return ApiResponse::success($report, '获取成功');
    }
}

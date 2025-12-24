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

    /**
     * 获取生产报工单列表
     *
     * @param Request $request 请求对象，支持 work_order_id（工单ID）和 start_date/end_date（日期范围）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的生产报工单列表，包含工单、工单明细项和报工人信息，按报工日期降序排列
     */
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

    /**
     * 创建生产报工单
     *
     * @param Request $request 请求对象，包含报工信息（工单ID、报工日期、数量、合格数量、工时等）
     * @return \Illuminate\Http\JsonResponse 返回创建的报工单信息，状态码 201，失败时返回错误消息
     */
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

    /**
     * 获取指定生产报工单详情
     *
     * @param int $id 生产报工单ID
     * @return \Illuminate\Http\JsonResponse 返回报工单详细信息，包含工单、工单明细项和报工人信息
     */
    public function show($id)
    {
        $report = ProductionReport::with(['workOrder', 'workOrderItem', 'reporter'])
            ->findOrFail($id);
        return ApiResponse::success($report, '获取成功');
    }
}

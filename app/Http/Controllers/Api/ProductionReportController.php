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
        // 注入生产服务
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
        // 构建查询，预加载工单、工单明细和报工人信息
        $query = ProductionReport::with(['workOrder', 'workOrderItem', 'reporter']);

        // 按工单ID筛选
        if ($request->has('work_order_id')) {
            $query->where('work_order_id', $request->work_order_id);
        }

        // 按日期范围筛选：开始日期
        if ($request->has('start_date')) {
            $query->whereDate('report_date', '>=', $request->start_date);
        }

        // 按日期范围筛选：结束日期
        if ($request->has('end_date')) {
            $query->whereDate('report_date', '<=', $request->end_date);
        }

        // 按报工日期倒序排列，返回分页结果
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
        // 验证报工单参数
        $validated = $request->validate([
            'work_order_id' => 'required|exists:work_orders,id',         // 工单ID（必填）
            'work_order_item_id' => 'nullable|exists:work_order_items,id', // 工单明细ID
            'report_date' => 'required|date',                             // 报工日期（必填）
            'quantity' => 'required|integer|min:1',                       // 报工数量（必填）
            'qualified_quantity' => 'nullable|integer|min:0',             // 合格数量
            'defective_quantity' => 'nullable|integer|min:0',             // 不合格数量
            'work_hours' => 'nullable|numeric|min:0',                     // 工时
            'overtime_hours' => 'nullable|numeric|min:0',                 // 加班工时
            'remark' => 'nullable|string',                                // 备注
        ]);

        try {
            // 调用生产服务创建报工记录
            $report = $this->productionService->reportWork($validated['work_order_id'], $validated);
            // 返回创建成功响应
            return response()->json($report, 201);
        } catch (\Exception $e) {
            // 创建失败返回错误消息
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
        // 根据ID查询报工单，预加载关联数据
        $report = ProductionReport::with(['workOrder', 'workOrderItem', 'reporter'])
            ->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($report, '获取成功');
    }
}

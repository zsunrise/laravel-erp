<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AccountsReceivable;
use App\Services\FinancialService;
use Illuminate\Http\Request;

class AccountsReceivableController extends Controller
{
    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        // 注入财务服务
        $this->financialService = $financialService;
    }

    /**
     * 获取应收账款列表
     *
     * @param Request $request 请求对象，支持 customer_id（客户ID）、status（状态）和 overdue（逾期）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的应收账款列表，包含客户和货币信息，按到期日升序排列
     */
    public function index(Request $request)
    {
        // 构建查询，预加载客户和货币信息
        $query = AccountsReceivable::with(['customer', 'currency']);

        // 按客户ID筛选
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // 按状态筛选（outstanding/partial/settled/overdue）
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 筛选逾期的应收账款
        if ($request->has('overdue')) {
            $query->where('status', 'overdue');
        }

        // 按到期日升序排列，返回分页结果
        return response()->json($query->orderBy('due_date', 'asc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建应收账款
     *
     * @param Request $request 请求对象，包含应收账款信息（客户ID、发票日期、到期日、金额等）
     * @return \Illuminate\Http\JsonResponse 返回创建的应收账款信息，状态码 201，失败时返回错误消息
     */
    public function store(Request $request)
    {
        // 验证应收账款参数
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',      // 客户ID（必填）
            'reference_type' => 'nullable|string',                // 关联业务类型
            'reference_id' => 'nullable|integer',                 // 关联业务ID
            'reference_no' => 'nullable|string',                  // 关联业务编号
            'invoice_date' => 'required|date',                    // 发票日期（必填）
            'due_date' => 'required|date|after:invoice_date',     // 到期日期
            'original_amount' => 'required|numeric|min:0',        // 原始金额（必填）
            'received_amount' => 'nullable|numeric|min:0',        // 已收金额
            'currency_id' => 'nullable|exists:currencies,id',     // 货币ID
            'remark' => 'nullable|string',                        // 备注
        ]);

        try {
            // 调用财务服务创建应收账款
            $receivable = $this->financialService->createReceivable($validated);
            // 创建成功返回 201 状态码
            return response()->json($receivable, 201);
        } catch (\Exception $e) {
            // 创建失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取指定应收账款详情
     *
     * @param int $id 应收账款ID
     * @return \Illuminate\Http\JsonResponse 返回应收账款详细信息，包含客户和货币信息
     */
    public function show($id)
    {
        // 根据ID查询应收账款，预加载客户和货币信息，找不到则抛出404
        $receivable = AccountsReceivable::with(['customer', 'currency'])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($receivable, '获取成功');
    }

    /**
     * 收款（应收账款）
     *
     * @param int $id 应收账款ID
     * @param Request $request 请求对象，包含 amount（收款金额）
     * @return \Illuminate\Http\JsonResponse 返回收款后的应收账款信息，失败时返回错误消息
     */
    public function receivePayment($id, Request $request)
    {
        // 验证收款参数
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',  // 收款金额（必填）
        ]);

        try {
            // 调用财务服务执行收款，更新应收账款状态
            $receivable = $this->financialService->receivePayment($id, $validated['amount']);
            // 返回更新后的应收账款信息
            return response()->json($receivable);
        } catch (\Exception $e) {
            // 收款失败（如金额超过剩余）返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 应收账款账龄分析
     *
     * @param Request $request 请求对象
     * @return \Illuminate\Http\JsonResponse 返回按账龄分组的应收账款金额统计（0-30天、31-60天、61-90天、90天以上）
     */
    public function ageAnalysis(Request $request)
    {
        // 构建查询：筛选未结清的应收账款
        $query = AccountsReceivable::with(['customer'])
            ->where('status', '!=', 'settled');

        // 按账龄分组统计剩余金额
        $ageGroups = [
            '0-30' => $query->clone()->where('age_days', '<=', 30)->sum('remaining_amount'),       // 0-30天
            '31-60' => $query->clone()->whereBetween('age_days', [31, 60])->sum('remaining_amount'), // 31-60天
            '61-90' => $query->clone()->whereBetween('age_days', [61, 90])->sum('remaining_amount'), // 61-90天
            '90+' => $query->clone()->where('age_days', '>', 90)->sum('remaining_amount'),         // 90天以上
        ];

        // 返回账龄分析结果
        return response()->json($ageGroups);
    }
}

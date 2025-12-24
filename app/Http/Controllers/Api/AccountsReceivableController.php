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
        $query = AccountsReceivable::with(['customer', 'currency']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('overdue')) {
            $query->where('status', 'overdue');
        }

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
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'reference_no' => 'nullable|string',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after:invoice_date',
            'original_amount' => 'required|numeric|min:0',
            'received_amount' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'remark' => 'nullable|string',
        ]);

        try {
            $receivable = $this->financialService->createReceivable($validated);
            return response()->json($receivable, 201);
        } catch (\Exception $e) {
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
        $receivable = AccountsReceivable::with(['customer', 'currency'])->findOrFail($id);
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
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            $receivable = $this->financialService->receivePayment($id, $validated['amount']);
            return response()->json($receivable);
        } catch (\Exception $e) {
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
        $query = AccountsReceivable::with(['customer'])
            ->where('status', '!=', 'settled');

        $ageGroups = [
            '0-30' => $query->clone()->where('age_days', '<=', 30)->sum('remaining_amount'),
            '31-60' => $query->clone()->whereBetween('age_days', [31, 60])->sum('remaining_amount'),
            '61-90' => $query->clone()->whereBetween('age_days', [61, 90])->sum('remaining_amount'),
            '90+' => $query->clone()->where('age_days', '>', 90)->sum('remaining_amount'),
        ];

        return response()->json($ageGroups);
    }
}

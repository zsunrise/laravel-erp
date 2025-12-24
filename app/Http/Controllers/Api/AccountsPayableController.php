<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AccountsPayable;
use App\Services\FinancialService;
use Illuminate\Http\Request;

class AccountsPayableController extends Controller
{
    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * 获取应付账款列表
     *
     * @param Request $request 请求对象，支持 supplier_id（供应商ID）、status（状态）和 overdue（逾期）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的应付账款列表，包含供应商和货币信息，按到期日升序排列
     */
    public function index(Request $request)
    {
        $query = AccountsPayable::with(['supplier', 'currency']);

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
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
     * 创建应付账款
     *
     * @param Request $request 请求对象，包含应付账款信息（供应商ID、发票日期、到期日、金额等）
     * @return \Illuminate\Http\JsonResponse 返回创建的应付账款信息，状态码 201，失败时返回错误消息
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'reference_no' => 'nullable|string',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after:invoice_date',
            'original_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'remark' => 'nullable|string',
        ]);

        try {
            $payable = $this->financialService->createPayable($validated);
            return response()->json($payable, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取指定应付账款详情
     *
     * @param int $id 应付账款ID
     * @return \Illuminate\Http\JsonResponse 返回应付账款详细信息，包含供应商和货币信息
     */
    public function show($id)
    {
        $payable = AccountsPayable::with(['supplier', 'currency'])->findOrFail($id);
        return ApiResponse::success($payable, '获取成功');
    }

    /**
     * 付款（应付账款）
     *
     * @param int $id 应付账款ID
     * @param Request $request 请求对象，包含 amount（付款金额）
     * @return \Illuminate\Http\JsonResponse 返回付款后的应付账款信息，失败时返回错误消息
     */
    public function makePayment($id, Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            $payable = $this->financialService->makePayment($id, $validated['amount']);
            return response()->json($payable);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

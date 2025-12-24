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
        // 注入财务服务
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
        // 构建查询，预加载供应商和货币信息
        $query = AccountsPayable::with(['supplier', 'currency']);

        // 按供应商ID筛选
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // 按状态筛选（outstanding/partial/settled/overdue）
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 筛选逾期的应付账款
        if ($request->has('overdue')) {
            $query->where('status', 'overdue');
        }

        // 按到期日升序排列，返回分页结果
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
        // 验证应付账款参数
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',      // 供应商ID（必填）
            'reference_type' => 'nullable|string',                // 关联业务类型
            'reference_id' => 'nullable|integer',                 // 关联业务ID
            'reference_no' => 'nullable|string',                  // 关联业务编号
            'invoice_date' => 'required|date',                    // 发票日期（必填）
            'due_date' => 'required|date|after:invoice_date',     // 到期日期
            'original_amount' => 'required|numeric|min:0',        // 原始金额（必填）
            'paid_amount' => 'nullable|numeric|min:0',            // 已付金额
            'currency_id' => 'nullable|exists:currencies,id',     // 货币ID
            'remark' => 'nullable|string',                        // 备注
        ]);

        try {
            // 调用财务服务创建应付账款
            $payable = $this->financialService->createPayable($validated);
            // 创建成功返回 201 状态码
            return response()->json($payable, 201);
        } catch (\Exception $e) {
            // 创建失败返回错误消息
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
        // 根据ID查询应付账款，预加载供应商和货币信息，找不到则抛出404
        $payable = AccountsPayable::with(['supplier', 'currency'])->findOrFail($id);
        // 返回标准化成功响应
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
        // 验证付款参数
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',  // 付款金额（必填）
        ]);

        try {
            // 调用财务服务执行付款，更新应付账款状态
            $payable = $this->financialService->makePayment($id, $validated['amount']);
            // 返回更新后的应付账款信息
            return response()->json($payable);
        } catch (\Exception $e) {
            // 付款失败（如金额超过剩余）返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

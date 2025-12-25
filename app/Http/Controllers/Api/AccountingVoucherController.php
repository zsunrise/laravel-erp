<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AccountingVoucher;
use App\Services\FinancialService;
use App\Services\ApprovalService;
use App\Constants\AccountingVoucherStatus;
use Illuminate\Http\Request;

class AccountingVoucherController extends Controller
{
    protected $financialService;
    protected $approvalService;

    public function __construct(FinancialService $financialService, ApprovalService $approvalService)
    {
        // 注入财务服务和审批服务
        $this->financialService = $financialService;
        $this->approvalService = $approvalService;
    }

    /**
     * 获取会计凭证列表
     *
     * @queryParam status string 凭证状态（draft/posted） Example: draft
     * @queryParam type string 凭证类型（general/adjustment/closing） Example: general
     * @queryParam start_date date 开始日期 Example: 2024-01-01
     * @queryParam end_date date 结束日期 Example: 2024-12-31
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 status（状态）、type（类型）和 start_date/end_date（日期范围）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的凭证列表，包含创建人和过账人信息，按凭证日期降序排列
     */
    public function index(Request $request)
    {
        // 构建查询，预加载创建人和过账人信息
        $query = AccountingVoucher::with(['creator', 'poster']);

        // 按状态筛选（支持整数状态值）
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 按凭证类型筛选
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // 按日期范围筛选：开始日期
        if ($request->has('start_date')) {
            $query->whereDate('voucher_date', '>=', $request->start_date);
        }

        // 按日期范围筛选：结束日期
        if ($request->has('end_date')) {
            $query->whereDate('voucher_date', '<=', $request->end_date);
        }

        // 按凭证日期倒序排列，返回分页结果
        return response()->json($query->orderBy('voucher_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建会计凭证
     *
     * @bodyParam voucher_date date required 凭证日期 Example: 2024-01-15
     * @bodyParam type string 凭证类型（general/adjustment/closing） Example: general
     * @bodyParam attachment_count integer 附件数量 Example: 2
     * @bodyParam remark string 备注 Example: 日常凭证
     * @bodyParam items array required 明细项（至少2条，借贷必须平衡） Example: 凭证明细数组
     * @bodyParam items.*.account_id integer required 科目ID Example: 1
     * @bodyParam items.*.direction string required 借贷方向（debit/credit） Example: debit
     * @bodyParam items.*.amount number required 金额（最小0） Example: 1000
     * @bodyParam items.*.summary string 摘要 Example: 支付货款
     * @bodyParam items.*.reference_type string 关联业务类型 Example: purchase_order
     * @bodyParam items.*.reference_id integer 关联业务ID Example: 1
     * @bodyParam items.*.reference_no string 关联业务编号 Example: PO001
     * @bodyParam items.*.sequence integer 分录序号 Example: 1
     * @param Request $request 请求对象，包含凭证信息和明细项数组（至少2条，借贷必须平衡）
     * @return \Illuminate\Http\JsonResponse 返回创建的凭证信息，状态码 201，失败时返回错误消息
     */
    public function store(Request $request)
    {
        // 验证凭证参数
        $validated = $request->validate([
            'voucher_date' => 'required|date',                              // 凭证日期（必填）
            'type' => 'sometimes|in:general,adjustment,closing',            // 凭证类型
            'attachment_count' => 'nullable|integer|min:0',                 // 附件数量
            'remark' => 'nullable|string',                                  // 备注
            'items' => 'required|array|min:2',                              // 明细项（至少2条）
            'items.*.account_id' => 'required|exists:chart_of_accounts,id', // 科目ID
            'items.*.direction' => 'required|in:debit,credit',              // 借贷方向
            'items.*.amount' => 'required|numeric|min:0',                   // 金额
            'items.*.summary' => 'nullable|string',                         // 摘要
            'items.*.reference_type' => 'nullable|string',                  // 关联业务类型
            'items.*.reference_id' => 'nullable|integer',                   // 关联业务ID
            'items.*.reference_no' => 'nullable|string',                    // 关联业务编号
            'items.*.sequence' => 'nullable|integer|min:0',                 // 分录序号
        ]);

        try {
            // 调用财务服务创建凭证（会检查借贷平衡）
            $voucher = $this->financialService->createVoucher($validated);
            // 返回创建成功响应
            return response()->json($voucher, 201);
        } catch (\Exception $e) {
            // 创建失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取指定凭证详情
     *
     * @param int $id 凭证ID
     * @return \Illuminate\Http\JsonResponse 返回凭证详细信息，包含明细项、科目、创建人和过账人信息
     */
    public function show($id)
    {
        // 根据ID查询凭证，预加载明细项、科目、创建人和过账人信息
        $voucher = AccountingVoucher::with(['items.account', 'creator', 'poster'])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($voucher, '获取成功');
    }

    /**
     * 更新会计凭证
     *
     * @param Request $request 请求对象，包含要更新的凭证字段和明细项
     * @param int $id 凭证ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的凭证信息，只能修改草稿状态的凭证，失败时返回错误消息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询凭证
        $voucher = AccountingVoucher::findOrFail($id);

        // 检查状态：只能修改草稿或已拒绝状态的凭证
        if ($voucher->status != AccountingVoucherStatus::DRAFT && $voucher->status != AccountingVoucherStatus::REJECTED) {
            return response()->json(['message' => '只能修改草稿或已拒绝状态的凭证'], 400);
        }

        $validated = $request->validate([
            'voucher_date' => 'sometimes|required|date',
            'type' => 'sometimes|in:general,adjustment,closing',
            'attachment_count' => 'nullable|integer|min:0',
            'remark' => 'nullable|string',
            'items' => 'sometimes|required|array|min:2',
            'items.*.account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.direction' => 'required|in:debit,credit',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.summary' => 'nullable|string',
            'items.*.reference_type' => 'nullable|string',
            'items.*.reference_id' => 'nullable|integer',
            'items.*.reference_no' => 'nullable|string',
            'items.*.sequence' => 'nullable|integer|min:0',
        ]);

        try {
            // 如果更新了明细项
            if (isset($validated['items'])) {
                // 删除原有明细项
                $voucher->items()->delete();
                $totalDebit = 0;   // 借方总额
                $totalCredit = 0;  // 贷方总额

                // 创建新的明细项并计算借贷总额
                foreach ($validated['items'] as $itemData) {
                    $voucher->items()->create($itemData);
                    if ($itemData['direction'] == 'debit') {
                        $totalDebit += $itemData['amount'];
                    } else {
                        $totalCredit += $itemData['amount'];
                    }
                }

                // 检查借贷是否平衡（允许0.01的误差）
                if (abs($totalDebit - $totalCredit) > 0.01) {
                    throw new \Exception('借贷不平衡');
                }
            }

            // 更新凭证主信息
            $voucher->update($validated);

            // 返回更新后的凭证信息
            return response()->json($voucher->load(['items.account', 'creator']));
        } catch (\Exception $e) {
            // 更新失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 删除会计凭证
     *
     * @param int $id 凭证ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，只能删除草稿状态的凭证
     */
    public function destroy($id)
    {
        // 根据ID查询凭证
        $voucher = AccountingVoucher::findOrFail($id);

        // 检查状态：只能删除草稿或已拒绝状态的凭证
        if ($voucher->status != AccountingVoucherStatus::DRAFT && $voucher->status != AccountingVoucherStatus::REJECTED) {
            return response()->json(['message' => '只能删除草稿或已拒绝状态的凭证'], 400);
        }

        // 删除凭证记录
        $voucher->delete();

        // 返回删除成功消息
        return response()->json(['message' => '凭证删除成功']);
    }

    /**
     * 提交审批（会计凭证）
     *
     * @param int $id 凭证ID
     * @return \Illuminate\Http\JsonResponse 返回提交后的凭证信息，失败时返回错误消息
     */
    public function submit($id)
    {
        try {
            $voucher = AccountingVoucher::findOrFail($id);

            // 检查状态：只有草稿或已拒绝状态的凭证才能提交审批
            if ($voucher->status != AccountingVoucherStatus::DRAFT && $voucher->status != AccountingVoucherStatus::REJECTED) {
                return response()->json(['message' => '只能提交草稿或已拒绝状态的凭证'], 400);
            }

            // 检查借贷是否平衡
            if (!$voucher->isBalanced()) {
                return response()->json(['message' => '凭证借贷不平衡，无法提交审批'], 400);
            }

            // 更新状态为待审核
            $voucher->update([
                'status' => AccountingVoucherStatus::PENDING,
            ]);

            return ApiResponse::success($voucher->load(['items.account', 'creator']), '凭证已提交审批');
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 启动审批流程（会计凭证）
     *
     * @bodyParam workflow_id integer required 工作流ID Example: 1
     * @param Request $request 请求对象，包含工作流ID
     * @param int $id 凭证ID
     * @return \Illuminate\Http\JsonResponse 返回启动的审批流程实例，失败时返回错误消息
     */
    public function startApproval(Request $request, $id)
    {
        try {
            $voucher = AccountingVoucher::findOrFail($id);

            // 检查状态：只有待审核状态的凭证才能启动审批流程
            if ($voucher->status != AccountingVoucherStatus::PENDING) {
                return response()->json(['message' => '只能启动待审核状态的凭证的审批流程'], 400);
            }

            $validated = $request->validate([
                'workflow_id' => 'required|exists:workflows,id',
            ]);

            // 启动工作流
            $instance = $this->approvalService->startWorkflow(
                $validated['workflow_id'],
                AccountingVoucher::class,
                $voucher->id,
                $voucher->voucher_no
            );

            // 更新凭证状态为审核中
            $voucher->update([
                'status' => AccountingVoucherStatus::UNDER_REVIEW,
            ]);

            return ApiResponse::success([
                'voucher' => $voucher->load(['items.account', 'creator']),
                'workflow_instance' => $instance,
            ], '审批流程已启动');
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 取消凭证
     *
     * @param int $id 凭证ID
     * @return \Illuminate\Http\JsonResponse 返回取消后的凭证信息，失败时返回错误消息
     */
    public function cancel($id)
    {
        try {
            $voucher = AccountingVoucher::findOrFail($id);

            // 检查状态：已过账的凭证不能取消
            if ($voucher->status == AccountingVoucherStatus::POSTED) {
                return response()->json(['message' => '已过账的凭证不能取消'], 400);
            }

            // 更新状态为已取消
            $voucher->update([
                'status' => AccountingVoucherStatus::CANCELLED,
            ]);

            return ApiResponse::success($voucher->load(['items.account', 'creator']), '凭证已取消');
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 过账（会计凭证）
     *
     * @param int $id 凭证ID
     * @return \Illuminate\Http\JsonResponse 返回过账后的凭证信息，失败时返回错误消息
     */
    public function post($id)
    {
        try {
            // 调用财务服务过账凭证（会生成总账分录）
            $voucher = $this->financialService->postVoucher($id);
            // 返回过账后的凭证信息
            return response()->json($voucher);
        } catch (\Exception $e) {
            // 过账失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

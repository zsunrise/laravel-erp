<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ApprovalRecord;
use App\Models\WorkflowInstance;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class ApprovalRecordController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        // 注入审批服务
        $this->approvalService = $approvalService;
    }

    /**
     * 获取审批记录列表
     *
     * @queryParam instance_id integer 流程实例ID Example: 1
     * @queryParam approver_id integer 审批人ID Example: 1
     * @queryParam status string 审批状态（pending/approved/rejected） Example: pending
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 instance_id（流程实例ID）、approver_id（审批人ID）和 status（状态）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的审批记录列表，包含流程实例、节点和审批人信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载流程实例、节点和审批人信息
        $query = ApprovalRecord::with(['instance', 'node', 'approver']);

        // 按流程实例ID筛选
        if ($request->has('instance_id')) {
            $query->where('instance_id', $request->instance_id);
        }

        // 按审批人id筛选
        if ($request->has('approver_id')) {
            $query->where('approver_id', $request->approver_id);
        }

        // 按审批状态筛选（pending/approved/rejected）
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 按创建时间倒序排列，返回分页结果
        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 审批通过
     *
     * @bodyParam comment string 审批意见 Example: 同意
     * @param int $instanceId 流程实例ID
     * @param Request $request 请求对象，包含 comment（审批意见）
     * @return \Illuminate\Http\JsonResponse 返回审批后的流程实例信息，失败时返回错误消息
     */
    public function approve($instanceId, Request $request)
    {
        // 验证审批参数
        $validated = $request->validate([
            'comment' => 'nullable|string',  // 审批意见（可选）
        ]);

        try {
            // 调用审批服务执行审批通过
            $instance = $this->approvalService->approve($instanceId, $validated['comment'] ?? null);
            // 返回审批成功响应
            return ApiResponse::success($instance, '审批成功');
        } catch (\Exception $e) {
            // 审批失败返回错误消息
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * 审批拒绝
     *
     * @bodyParam comment string 拒绝原因 Example: 金额超出预算
     * @param int $instanceId 流程实例ID
     * @param Request $request 请求对象，包含 comment（拒绝原因）
     * @return \Illuminate\Http\JsonResponse 返回拒绝后的流程实例信息，失败时返回错误消息
     */
    public function reject($instanceId, Request $request)
    {
        // 验证拒绝参数
        $validated = $request->validate([
            'comment' => 'nullable|string',  // 拒绝原因（可选）
        ]);

        try {
            // 调用审批服务执行审批拒绝
            $instance = $this->approvalService->reject($instanceId, $validated['comment'] ?? null);
            // 返回拒绝成功响应
            return ApiResponse::success($instance, '拒绝成功');
        } catch (\Exception $e) {
            // 拒绝失败返回错误消息
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * 获取待审批列表
     *
     * @return \Illuminate\Http\JsonResponse 返回当前登录用户的待审批流程实例列表
     */
    public function pendingApprovals()
    {
        // 调用审批服务获取当前用户的待审批流程实例
        $instances = $this->approvalService->getPendingApprovals(auth()->id());
        // 返回待审批列表
        return response()->json($instances);
    }

    /**
     * 获取审批历史记录
     *
     * @param int $instanceId 流程实例ID
     * @return \Illuminate\Http\JsonResponse 返回流程实例的完整审批历史记录，包含所有审批记录
     */
    public function history($instanceId)
    {
        // 根据ID查询流程实例，预加载全部关联信息
        $instance = WorkflowInstance::with([
            'workflow',                     // 工作流定义
            'currentNode',                  // 当前节点
            'starter',                      // 发起人
            'approvalRecords.approver',     // 审批记录及审批人
            'approvalRecords.node'          // 审批记录对应的节点
        ])->findOrFail($instanceId);
        
        // 查询该实例的所有审批记录，按时间正序排列
        $records = ApprovalRecord::with(['node', 'approver', 'transferredTo'])
            ->where('instance_id', $instanceId)
            ->orderBy('created_at', 'asc')
            ->get();

        // 返回完整的审批历史记录
        return ApiResponse::success([
            'instance' => $instance,
            'records' => $records,
        ], '获取成功');
    }
}

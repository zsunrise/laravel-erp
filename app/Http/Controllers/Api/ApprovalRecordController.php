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
        $this->approvalService = $approvalService;
    }

    /**
     * 获取审批记录列表
     *
     * @param Request $request 请求对象，支持 instance_id（流程实例ID）、approver_id（审批人ID）和 status（状态）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的审批记录列表，包含流程实例、节点和审批人信息
     */
    public function index(Request $request)
    {
        $query = ApprovalRecord::with(['instance', 'node', 'approver']);

        if ($request->has('instance_id')) {
            $query->where('instance_id', $request->instance_id);
        }

        if ($request->has('approver_id')) {
            $query->where('approver_id', $request->approver_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 审批通过
     *
     * @param int $instanceId 流程实例ID
     * @param Request $request 请求对象，包含 comment（审批意见）
     * @return \Illuminate\Http\JsonResponse 返回审批后的流程实例信息，失败时返回错误消息
     */
    public function approve($instanceId, Request $request)
    {
        $validated = $request->validate([
            'comment' => 'nullable|string',
        ]);

        try {
            $instance = $this->approvalService->approve($instanceId, $validated['comment'] ?? null);
            return ApiResponse::success($instance, '审批成功');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * 审批拒绝
     *
     * @param int $instanceId 流程实例ID
     * @param Request $request 请求对象，包含 comment（拒绝原因）
     * @return \Illuminate\Http\JsonResponse 返回拒绝后的流程实例信息，失败时返回错误消息
     */
    public function reject($instanceId, Request $request)
    {
        $validated = $request->validate([
            'comment' => 'nullable|string',
        ]);

        try {
            $instance = $this->approvalService->reject($instanceId, $validated['comment'] ?? null);
            return ApiResponse::success($instance, '拒绝成功');
        } catch (\Exception $e) {
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
        $instances = $this->approvalService->getPendingApprovals(auth()->id());
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
        $instance = WorkflowInstance::with([
            'workflow',
            'currentNode',
            'starter',
            'approvalRecords.approver',
            'approvalRecords.node'
        ])->findOrFail($instanceId);
        
        $records = ApprovalRecord::with(['node', 'approver', 'transferredTo'])
            ->where('instance_id', $instanceId)
            ->orderBy('created_at', 'asc')
            ->get();

        return ApiResponse::success([
            'instance' => $instance,
            'records' => $records,
        ], '获取成功');
    }
}

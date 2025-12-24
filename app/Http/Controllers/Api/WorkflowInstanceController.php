<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\WorkflowInstance;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class WorkflowInstanceController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * 获取工作流实例列表
     *
     * @param Request $request 请求对象，支持 workflow_id（工作流ID）、status（状态）和 reference_type/reference_id（关联信息）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的工作流实例列表，包含工作流、当前节点和发起人信息，按创建时间降序排列
     */
    public function index(Request $request)
    {
        $query = WorkflowInstance::with(['workflow', 'currentNode', 'starter']);

        if ($request->has('workflow_id')) {
            $query->where('workflow_id', $request->workflow_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->has('reference_id')) {
            $query->where('reference_id', $request->reference_id);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 启动工作流实例
     *
     * @param Request $request 请求对象，包含工作流ID和关联业务信息
     * @return \Illuminate\Http\JsonResponse 返回创建的工作流实例信息，状态码 201，失败时返回错误消息
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'workflow_id' => 'required|exists:workflows,id',
            'reference_type' => 'required|string',
            'reference_id' => 'required|integer',
            'reference_no' => 'nullable|string',
        ]);

        try {
            $instance = $this->approvalService->startWorkflow(
                $validated['workflow_id'],
                $validated['reference_type'],
                $validated['reference_id'],
                $validated['reference_no'] ?? null
            );
            return response()->json($instance, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取指定工作流实例详情
     *
     * @param int $id 工作流实例ID
     * @return \Illuminate\Http\JsonResponse 返回工作流实例详细信息，包含工作流、当前节点、发起人和审批记录信息
     */
    public function show($id)
    {
        $instance = WorkflowInstance::with([
            'workflow',
            'currentNode',
            'starter',
            'approvalRecords.approver',
            'approvalRecords.node'
        ])->findOrFail($id);
        return ApiResponse::success($instance, '获取成功');
    }
}

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
        // 注入审批服务
        $this->approvalService = $approvalService;
    }

    /**
     * 获取工作流实例列表
     *
     * @queryParam workflow_id integer 工作流ID Example: 1
     * @queryParam status string 实例状态（pending/approved/rejected/cancelled） Example: pending
     * @queryParam reference_type string 关联业务类型 Example: purchase_order
     * @queryParam reference_id integer 关联业务ID Example: 1
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 workflow_id（工作流ID）、status（状态）和 reference_type/reference_id（关联信息）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的工作流实例列表，包含工作流、当前节点和发起人信息，按创建时间降序排列
     */
    public function index(Request $request)
    {
        // 构建查询，预加载工作流、当前节点和发起人信息
        $query = WorkflowInstance::with(['workflow', 'currentNode', 'starter']);

        // 按工作流ID筛选
        if ($request->has('workflow_id')) {
            $query->where('workflow_id', $request->workflow_id);
        }

        // 按实例状态筛选（pending/approved/rejected/cancelled）
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 按关联业务类型筛选
        if ($request->has('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        // 按关联业务ID筛选
        if ($request->has('reference_id')) {
            $query->where('reference_id', $request->reference_id);
        }

        // 按创建时间倒序排列，返回分页结果
        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 启动工作流实例
     *
     * @bodyParam workflow_id integer required 工作流ID Example: 1
     * @bodyParam reference_type string required 关联业务类型 Example: purchase_order
     * @bodyParam reference_id integer required 关联业务ID Example: 1
     * @bodyParam reference_no string 关联业务编号 Example: PO001
     * @param Request $request 请求对象，包含工作流ID和关联业务信息
     * @return \Illuminate\Http\JsonResponse 返回创建的工作流实例信息，状态码 201，失败时返回错误消息
     */
    public function store(Request $request)
    {
        // 验证启动工作流参数
        $validated = $request->validate([
            'workflow_id' => 'required|exists:workflows,id',   // 工作流ID（必填）
            'reference_type' => 'required|string',             // 关联业务类型（如 purchase_order）
            'reference_id' => 'required|integer',              // 关联业务ID
            'reference_no' => 'nullable|string',               // 关联业务编号
        ]);

        try {
            // 调用审批服务启动工作流
            $instance = $this->approvalService->startWorkflow(
                $validated['workflow_id'],
                $validated['reference_type'],
                $validated['reference_id'],
                $validated['reference_no'] ?? null
            );
            // 启动成功返回 201 状态码
            return response()->json($instance, 201);
        } catch (\Exception $e) {
            // 启动失败返回错误消息
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
        // 根据ID查询工作流实例，预加载全部关联信息
        $instance = WorkflowInstance::with([
            'workflow',                     // 工作流定义
            'currentNode',                  // 当前节点
            'starter',                      // 发起人
            'approvalRecords.approver',     // 审批记录及审批人
            'approvalRecords.node'          // 审批记录对应的节点
        ])->findOrFail($id); // 找不到则抛出404异常
        // 返回标准化成功响应
        return ApiResponse::success($instance, '获取成功');
    }
}

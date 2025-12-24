<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkflowController extends Controller
{
    /**
     * 获取工作流列表
     *
     * @queryParam type string 工作流类型（purchase_order/sales_order/expense/work_order/production_plan） Example: purchase_order
     * @queryParam is_active boolean 是否激活（1:激活, 0:未激活） Example: 1
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 type（类型）和 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的工作流列表，包含创建人信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载创建人信息
        $query = Workflow::with(['creator']);

        // 按工作流类型筛选
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 按创建时间倒序排列，返回分页结果
        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建工作流
     *
     * @bodyParam name string required 工作流名称 Example: 采购订单审批流程
     * @bodyParam code string required 工作流编码（唯一） Example: PO_APPROVAL
     * @bodyParam type string required 工作流类型（purchase_order/sales_order/expense/work_order/production_plan） Example: purchase_order
     * @bodyParam description string 描述 Example: 采购订单审批流程
     * @bodyParam is_active boolean 是否激活 Example: true
     * @bodyParam nodes array required 节点数组（至少一个） Example: 工作流节点数组
     * @bodyParam nodes.*.node_name string required 节点名称 Example: 提交
     * @bodyParam nodes.*.node_type string required 节点类型（start/approval/condition/end） Example: approval
     * @bodyParam nodes.*.sequence integer required 节点顺序（最小1） Example: 1
     * @bodyParam nodes.*.approval_type string 审批类型（single/all/any） Example: single
     * @bodyParam nodes.*.approver_config array 审批人配置 Example: 审批人配置对象
     * @bodyParam nodes.*.condition_config array 条件配置 Example: 条件配置对象
     * @bodyParam nodes.*.next_nodes array 下一节点 Example: 2,3
     * @bodyParam nodes.*.timeout_hours integer 超时时间（小时） Example: 24
     * @bodyParam nodes.*.is_required boolean 是否必须 Example: true
     * @bodyParam nodes.*.remark string 备注 Example: 重要节点
     * @param Request $request 请求对象，包含工作流信息和节点数组
     * @return \Illuminate\Http\JsonResponse 返回创建的工作流信息，状态码 201
     */
    public function store(Request $request)
    {
        // 验证工作流参数
        $validated = $request->validate([
            'name' => 'required|string|max:255',                           // 工作流名称（必填）
            'code' => 'required|string|unique:workflows,code',             // 工作流编码（唯一）
            'type' => 'required|in:purchase_order,sales_order,expense,work_order,production_plan', // 类型
            'description' => 'nullable|string',                            // 描述
            'is_active' => 'sometimes|boolean',                            // 是否激活
            'nodes' => 'required|array|min:1',                             // 节点数组（至少一个）
            'nodes.*.node_name' => 'required|string|max:255',              // 节点名称
            'nodes.*.node_type' => 'required|in:start,approval,condition,end', // 节点类型
            'nodes.*.sequence' => 'required|integer|min:1',                // 节点顺序
            'nodes.*.approval_type' => 'nullable|in:single,all,any',       // 审批类型
            'nodes.*.approver_config' => 'nullable|array',                 // 审批人配置
            'nodes.*.condition_config' => 'nullable|array',                // 条件配置
            'nodes.*.next_nodes' => 'nullable|array',                      // 下一节点
            'nodes.*.timeout_hours' => 'nullable|integer|min:0',           // 超时时间
            'nodes.*.is_required' => 'nullable|boolean',                   // 是否必须
            'nodes.*.remark' => 'nullable|string',                         // 备注
        ]);

        // 使用事务确保数据一致性
        return DB::transaction(function () use ($validated) {
            // 创建工作流主记录
            $workflow = Workflow::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->id(), // 记录创建人
            ]);

            // 创建工作流节点
            foreach ($validated['nodes'] as $nodeData) {
                $workflow->nodes()->create($nodeData);
            }

            // 返回新建工作流信息（包含节点和创建人）
            return response()->json($workflow->load(['nodes', 'creator']), 201);
        });
    }

    /**
     * 获取指定工作流详情
     *
     * @param int $id 工作流ID
     * @return \Illuminate\Http\JsonResponse 返回工作流详细信息，包含节点和创建人信息
     */
    public function show($id)
    {
        // 根据ID查询工作流，预加载节点和创建人信息，找不到则抛出404
        $workflow = Workflow::with(['nodes', 'creator'])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($workflow, '获取成功');
    }

    /**
     * 更新工作流信息
     *
     * @param Request $request 请求对象，包含要更新的工作流字段
     * @param int $id 工作流ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的工作流信息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询工作流
        $workflow = Workflow::findOrFail($id);

        // 验证更新参数
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:workflows,code,' . $id,
            'type' => 'sometimes|required|in:purchase_order,sales_order,expense,work_order,production_plan',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        // 更新工作流信息
        $workflow->update($validated);

        // 返回更新后的工作流信息
        return response()->json($workflow->load(['nodes', 'creator']));
    }

    /**
     * 删除工作流
     *
     * @param int $id 工作流ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，如果工作流已有实例则返回错误消息
     */
    public function destroy($id)
    {
        // 根据ID查询工作流
        $workflow = Workflow::findOrFail($id);

        // 检查是否有关联实例，有则不允许删除
        if ($workflow->instances()->count() > 0) {
            return response()->json(['message' => '该流程已有实例，无法删除'], 400);
        }

        // 删除工作流记录
        $workflow->delete();

        // 返回删除成功消息
        return response()->json(['message' => '流程删除成功']);
    }
}

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
     * @param Request $request 请求对象，支持 type（类型）和 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的工作流列表，包含创建人信息
     */
    public function index(Request $request)
    {
        $query = Workflow::with(['creator']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建工作流
     *
     * @param Request $request 请求对象，包含工作流信息和节点数组
     * @return \Illuminate\Http\JsonResponse 返回创建的工作流信息，状态码 201
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:workflows,code',
            'type' => 'required|in:purchase_order,sales_order,expense,work_order,production_plan',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'nodes' => 'required|array|min:1',
            'nodes.*.node_name' => 'required|string|max:255',
            'nodes.*.node_type' => 'required|in:start,approval,condition,end',
            'nodes.*.sequence' => 'required|integer|min:1',
            'nodes.*.approval_type' => 'nullable|in:single,all,any',
            'nodes.*.approver_config' => 'nullable|array',
            'nodes.*.condition_config' => 'nullable|array',
            'nodes.*.next_nodes' => 'nullable|array',
            'nodes.*.timeout_hours' => 'nullable|integer|min:0',
            'nodes.*.is_required' => 'nullable|boolean',
            'nodes.*.remark' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $workflow = Workflow::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['nodes'] as $nodeData) {
                $workflow->nodes()->create($nodeData);
            }

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
        $workflow = Workflow::with(['nodes', 'creator'])->findOrFail($id);
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
        $workflow = Workflow::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:workflows,code,' . $id,
            'type' => 'sometimes|required|in:purchase_order,sales_order,expense,work_order,production_plan',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $workflow->update($validated);

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
        $workflow = Workflow::findOrFail($id);

        if ($workflow->instances()->count() > 0) {
            return response()->json(['message' => '该流程已有实例，无法删除'], 400);
        }

        $workflow->delete();

        return response()->json(['message' => '流程删除成功']);
    }
}

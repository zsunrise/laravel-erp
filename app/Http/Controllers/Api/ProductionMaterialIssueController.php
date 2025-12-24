<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ProductionMaterialIssue;
use Illuminate\Http\Request;

class ProductionMaterialIssueController extends Controller
{
    /**
     * 获取生产领料单列表
     *
     * @queryParam work_order_id integer 工单ID Example: 1
     * @queryParam warehouse_id integer 仓库ID Example: 1
     * @queryParam type string 类型（issue/return，领料/退料） Example: issue
     * @queryParam status string 状态（draft/approved/completed/cancelled） Example: approved
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 work_order_id（工单ID）、warehouse_id（仓库ID）、type（类型）和 status（状态）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的生产领料单列表，包含工单、仓库和创建人信息，按领料日期降序排列
     */
    public function index(Request $request)
    {
        // 构建查询，预加载工单、仓库和创建人信息
        $query = ProductionMaterialIssue::with(['workOrder', 'warehouse', 'creator']);

        // 按工单ID筛选
        if ($request->has('work_order_id')) {
            $query->where('work_order_id', $request->work_order_id);
        }

        // 按仓库筛选
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 按类型筛选（领料/退料）
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // 按状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 按领料日期倒序排列，返回分页结果
        return response()->json($query->orderBy('issue_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 获取指定生产领料单详情
     *
     * @param int $id 生产领料单ID
     * @return \Illuminate\Http\JsonResponse 返回领料单详细信息，包含工单、仓库、创建人、审批人和明细项信息
     */
    public function show($id)
    {
        // 根据ID查询领料单，预加载关联数据
        $materialIssue = ProductionMaterialIssue::with([
            'workOrder', 'warehouse', 'creator', 'approver',
            'items.product', 'items.location'
        ])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($materialIssue, '获取成功');
    }
}

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
     * @param Request $request 请求对象，支持 work_order_id（工单ID）、warehouse_id（仓库ID）、type（类型）和 status（状态）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的生产领料单列表，包含工单、仓库和创建人信息，按领料日期降序排列
     */
    public function index(Request $request)
    {
        $query = ProductionMaterialIssue::with(['workOrder', 'warehouse', 'creator']);

        if ($request->has('work_order_id')) {
            $query->where('work_order_id', $request->work_order_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

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
        $materialIssue = ProductionMaterialIssue::with([
            'workOrder', 'warehouse', 'creator', 'approver',
            'items.product', 'items.location'
        ])->findOrFail($id);
        return ApiResponse::success($materialIssue, '获取成功');
    }
}

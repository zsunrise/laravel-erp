<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ProductionMaterialIssue;
use Illuminate\Http\Request;

class ProductionMaterialIssueController extends Controller
{
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

    public function show($id)
    {
        $materialIssue = ProductionMaterialIssue::with([
            'workOrder', 'warehouse', 'creator', 'approver',
            'items.product', 'items.location'
        ])->findOrFail($id);
        return ApiResponse::success($materialIssue, '获取成功');
    }
}

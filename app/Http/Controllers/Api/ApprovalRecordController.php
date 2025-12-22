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

    public function approve($instanceId, Request $request)
    {
        $validated = $request->validate([
            'comment' => 'nullable|string',
        ]);

        try {
            $instance = $this->approvalService->approve($instanceId, $validated['comment'] ?? null);
            return response()->json($instance);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function reject($instanceId, Request $request)
    {
        $validated = $request->validate([
            'comment' => 'nullable|string',
        ]);

        try {
            $instance = $this->approvalService->reject($instanceId, $validated['comment'] ?? null);
            return response()->json($instance);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function pendingApprovals()
    {
        $instances = $this->approvalService->getPendingApprovals(auth()->id());
        return response()->json($instances);
    }

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

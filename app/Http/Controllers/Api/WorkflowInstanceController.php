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

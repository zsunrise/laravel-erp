<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowNode;
use App\Models\ApprovalRecord;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function startWorkflow($workflowId, $referenceType, $referenceId, $referenceNo = null)
    {
        $workflow = Workflow::with('nodes')->findOrFail($workflowId);

        if (!$workflow->is_active) {
            throw new \Exception('流程未启用');
        }

        return DB::transaction(function () use ($workflow, $referenceType, $referenceId, $referenceNo) {
            $startNode = $workflow->nodes()->where('node_type', 'start')->first();
            
            if (!$startNode) {
                throw new \Exception('流程缺少开始节点');
            }

            $instance = WorkflowInstance::create([
                'workflow_id' => $workflow->id,
                'instance_no' => 'WF' . date('YmdHis') . rand(1000, 9999),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_no' => $referenceNo,
                'status' => 'pending',
                'current_node_id' => $startNode->id,
                'started_by' => auth()->id(),
                'started_at' => now(),
            ]);

            $this->moveToNextNode($instance);

            return $instance->load(['workflow', 'currentNode', 'starter']);
        });
    }

    public function approve($instanceId, $comment = null)
    {
        $instance = WorkflowInstance::with(['currentNode', 'workflow'])->findOrFail($instanceId);

        if ($instance->status != 'pending') {
            throw new \Exception('流程实例状态不允许审批');
        }

        $currentNode = $instance->currentNode;
        if (!$currentNode) {
            throw new \Exception('当前节点不存在');
        }

        return DB::transaction(function () use ($instance, $currentNode, $comment) {
            ApprovalRecord::create([
                'instance_id' => $instance->id,
                'node_id' => $currentNode->id,
                'approver_id' => auth()->id(),
                'action' => 'approve',
                'status' => 'approved',
                'comment' => $comment,
                'approved_at' => now(),
            ]);

            $this->moveToNextNode($instance);

            return $instance->load(['workflow', 'currentNode', 'approvalRecords.approver']);
        });
    }

    public function reject($instanceId, $comment = null)
    {
        $instance = WorkflowInstance::findOrFail($instanceId);

        if ($instance->status != 'pending') {
            throw new \Exception('流程实例状态不允许拒绝');
        }

        return DB::transaction(function () use ($instance, $comment) {
            ApprovalRecord::create([
                'instance_id' => $instance->id,
                'node_id' => $instance->current_node_id,
                'approver_id' => auth()->id(),
                'action' => 'reject',
                'status' => 'rejected',
                'comment' => $comment,
                'approved_at' => now(),
            ]);

            $instance->update([
                'status' => 'rejected',
                'completed_at' => now(),
            ]);

            return $instance->load(['workflow', 'approvalRecords.approver']);
        });
    }

    protected function moveToNextNode(WorkflowInstance $instance)
    {
        $currentNode = $instance->currentNode;
        
        if ($currentNode->node_type == 'end') {
            $instance->update([
                'status' => 'approved',
                'completed_at' => now(),
            ]);
            return;
        }

        $nextNodes = $currentNode->next_nodes;
        if (empty($nextNodes)) {
            $instance->update([
                'status' => 'approved',
                'completed_at' => now(),
            ]);
            return;
        }

        $nextNodeId = $nextNodes[0];
        $nextNode = WorkflowNode::find($nextNodeId);

        if ($nextNode) {
            $instance->update([
                'current_node_id' => $nextNode->id,
            ]);
        } else {
            $instance->update([
                'status' => 'approved',
                'completed_at' => now(),
            ]);
        }
    }

    public function getPendingApprovals($userId)
    {
        $instances = WorkflowInstance::with(['workflow', 'currentNode', 'reference'])
            ->where('status', 'pending')
            ->whereHas('currentNode', function($query) use ($userId) {
                $query->where(function($q) use ($userId) {
                    $q->whereJsonContains('approver_config->user_ids', $userId)
                      ->orWhereHas('workflow', function($w) use ($userId) {
                          $w->whereHas('instances', function($i) use ($userId) {
                              $i->where('started_by', $userId);
                          });
                      });
                });
            })
            ->get();

        return $instances;
    }
}


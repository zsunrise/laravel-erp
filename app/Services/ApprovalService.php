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

        // 检查当前用户是否有权限审批该节点
        if (!$this->canApproveNode($currentNode, auth()->id())) {
            throw new \Exception('您没有权限审批该节点');
        }

        // 检查是否已经审批过（防止重复审批）
        $existingRecord = ApprovalRecord::where('instance_id', $instance->id)
            ->where('node_id', $currentNode->id)
            ->where('approver_id', auth()->id())
            ->where('status', 'approved')
            ->first();
        
        if ($existingRecord) {
            throw new \Exception('您已经审批过该节点');
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

            // 检查是否需要移动到下一个节点
            $this->checkAndMoveToNextNode($instance, $currentNode);

            return $instance->load(['workflow', 'currentNode', 'approvalRecords.approver']);
        });
    }

    public function reject($instanceId, $comment = null)
    {
        $instance = WorkflowInstance::with(['currentNode'])->findOrFail($instanceId);

        if ($instance->status != 'pending') {
            throw new \Exception('流程实例状态不允许拒绝');
        }

        $currentNode = $instance->currentNode;
        if (!$currentNode) {
            throw new \Exception('当前节点不存在');
        }

        // 检查当前用户是否有权限审批该节点
        if (!$this->canApproveNode($currentNode, auth()->id())) {
            throw new \Exception('您没有权限拒绝该节点');
        }

        return DB::transaction(function () use ($instance, $currentNode, $comment) {
            ApprovalRecord::create([
                'instance_id' => $instance->id,
                'node_id' => $currentNode->id,
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

    /**
     * 检查并移动到下一个节点
     * 根据审批类型（单人审批、会签、或签）决定是否移动到下一个节点
     */
    protected function checkAndMoveToNextNode(WorkflowInstance $instance, WorkflowNode $currentNode)
    {
        // 如果是结束节点，直接完成流程
        if ($currentNode->node_type == 'end') {
            $instance->update([
                'status' => 'approved',
                'completed_at' => now(),
            ]);
            return;
        }

        // 如果不是审批节点，直接移动到下一个节点
        if ($currentNode->node_type != 'approval') {
            $this->moveToNextNode($instance);
            return;
        }

        // 获取当前节点的审批类型
        $approvalType = $currentNode->approval_type ?? 'single';
        
        // 获取当前节点的所有审批人配置
        $approverConfig = $currentNode->approver_config ?? [];
        $userIds = $approverConfig['user_ids'] ?? [];
        
        if (empty($userIds)) {
            // 如果没有配置审批人，直接移动到下一个节点
            $this->moveToNextNode($instance);
            return;
        }

        // 获取当前节点已有的审批记录
        $approvalRecords = ApprovalRecord::where('instance_id', $instance->id)
            ->where('node_id', $currentNode->id)
            ->get();

        $approvedCount = $approvalRecords->where('status', 'approved')->count();
        $rejectedCount = $approvalRecords->where('status', 'rejected')->count();

        // 如果有拒绝记录，流程应该已经结束（在reject方法中处理）
        if ($rejectedCount > 0) {
            return;
        }

        // 根据审批类型判断是否可以移动到下一个节点
        $canMoveNext = false;
        
        switch ($approvalType) {
            case 'single':
                // 单人审批：只要有一个人审批通过即可
                $canMoveNext = $approvedCount >= 1;
                break;
                
            case 'all':
                // 会签：需要所有审批人都审批通过
                $canMoveNext = $approvedCount >= count($userIds);
                break;
                
            case 'any':
                // 或签：只要有一个人审批通过即可
                $canMoveNext = $approvedCount >= 1;
                break;
                
            default:
                $canMoveNext = $approvedCount >= 1;
                break;
        }

        if ($canMoveNext) {
            $this->moveToNextNode($instance);
        }
    }

    /**
     * 移动到下一个节点
     */
    protected function moveToNextNode(WorkflowInstance $instance)
    {
        $instance->refresh();
        $currentNode = $instance->currentNode;
        
        if (!$currentNode) {
            $instance->update([
                'status' => 'approved',
                'completed_at' => now(),
            ]);
            return;
        }
        
        if ($currentNode->node_type == 'end') {
            $instance->update([
                'status' => 'approved',
                'completed_at' => now(),
            ]);
            return;
        }

        $nextNodes = $currentNode->next_nodes;
        if (empty($nextNodes) || !is_array($nextNodes)) {
            $instance->update([
                'status' => 'approved',
                'completed_at' => now(),
            ]);
            return;
        }

        // 取第一个下一个节点（如果有多个分支，后续可以扩展条件判断）
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

    /**
     * 检查用户是否有权限审批该节点
     */
    protected function canApproveNode(WorkflowNode $node, $userId)
    {
        // 如果不是审批节点，返回false
        if ($node->node_type != 'approval') {
            return false;
        }

        $approverConfig = $node->approver_config ?? [];
        
        // 检查用户ID是否在审批人列表中
        $userIds = $approverConfig['user_ids'] ?? [];
        if (in_array($userId, $userIds)) {
            return true;
        }

        // TODO: 可以扩展检查角色ID和部门ID
        // $roleIds = $approverConfig['role_ids'] ?? [];
        // $deptIds = $approverConfig['dept_ids'] ?? [];

        return false;
    }

    public function getPendingApprovals($userId)
    {
        $instances = WorkflowInstance::with(['workflow', 'currentNode', 'reference', 'starter'])
            ->where('status', 'pending')
            ->whereHas('currentNode', function($query) use ($userId) {
                $query->where('node_type', 'approval')
                    ->where(function($q) use ($userId) {
                        // 检查用户ID是否在审批人配置中
                        $q->whereJsonContains('approver_config->user_ids', $userId);
                        // TODO: 可以扩展检查角色和部门
                        // ->orWhereJsonContains('approver_config->role_ids', $userRoleId)
                        // ->orWhereJsonContains('approver_config->dept_ids', $userDeptId);
                    });
            })
            ->get()
            ->filter(function($instance) use ($userId) {
                // 二次过滤：检查是否已经审批过（防止重复显示）
                $currentNode = $instance->currentNode;
                if (!$currentNode) {
                    return false;
                }

                $approvalType = $currentNode->approval_type ?? 'single';
                $approverConfig = $currentNode->approver_config ?? [];
                $userIds = $approverConfig['user_ids'] ?? [];

                // 检查是否已经审批过
                $hasApproved = ApprovalRecord::where('instance_id', $instance->id)
                    ->where('node_id', $currentNode->id)
                    ->where('approver_id', $userId)
                    ->whereIn('status', ['approved', 'rejected'])
                    ->exists();

                // 如果是会签（all），即使已审批，只要节点未完成，仍需要显示
                if ($approvalType == 'all' && $hasApproved) {
                    // 检查是否所有审批人都已审批
                    $allApproved = ApprovalRecord::where('instance_id', $instance->id)
                        ->where('node_id', $currentNode->id)
                        ->whereIn('status', ['approved', 'rejected'])
                        ->count() >= count($userIds);
                    
                    if ($allApproved) {
                        return false; // 所有审批人都已审批，不显示
                    }
                } else if ($hasApproved) {
                    return false; // 已审批，不显示
                }

                return true;
            })
            ->values();

        return $instances;
    }
}


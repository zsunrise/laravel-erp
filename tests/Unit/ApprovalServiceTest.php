<?php

namespace Tests\Unit;

use App\Constants\ApprovalRecordStatus;
use App\Constants\OrderStatus;
use App\Constants\WorkflowStatus;
use App\Models\ApprovalRecord;
use App\Models\PurchaseOrder;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowNode;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalService $approvalService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->approvalService = new ApprovalService();
        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

    public function test_single_approval_approves_workflow_and_updates_purchase_order()
    {
        $order = PurchaseOrder::factory()->create([
            'status' => OrderStatus::DRAFT,
        ]);

        $workflow = Workflow::create([
            'name' => '采购审批流程',
            'code' => 'purchase_order_approval',
            'type' => 'purchase_order',
            'description' => '测试用采购审批流程',
            'is_active' => true,
            'version' => 1,
            'created_by' => $this->user->id,
        ]);

        $startNode = WorkflowNode::create([
            'workflow_id' => $workflow->id,
            'node_name' => '开始',
            'node_type' => 'start',
            'sequence' => 1,
            'next_nodes' => [],
        ]);

        $approvalNode = WorkflowNode::create([
            'workflow_id' => $workflow->id,
            'node_name' => '审批',
            'node_type' => 'approval',
            'sequence' => 2,
            'approval_type' => 'single',
            'approver_config' => [
                'user_ids' => [$this->user->id],
            ],
            'next_nodes' => [],
        ]);

        $startNode->update([
            'next_nodes' => [$approvalNode->id],
        ]);

        $instance = $this->approvalService->startWorkflow(
            $workflow->id,
            PurchaseOrder::class,
            $order->id,
            $order->order_no
        );

        $instance->refresh();
        $this->assertEquals(WorkflowStatus::PENDING, $instance->status);
        $this->assertEquals($approvalNode->id, $instance->current_node_id);

        $this->approvalService->approve($instance->id, '同意');

        $instance->refresh();
        $order->refresh();

        $this->assertEquals(WorkflowStatus::APPROVED, $instance->status);
        $this->assertEquals(OrderStatus::APPROVED, $order->status);
        $this->assertEquals($this->user->id, $order->approved_by);
        $this->assertNotNull($order->approved_at);
        $this->assertEquals(1, ApprovalRecord::count());

        $record = ApprovalRecord::first();
        $this->assertEquals(ApprovalRecordStatus::APPROVED, $record->status);
        $this->assertEquals($this->user->id, $record->approver_id);
    }

    public function test_get_pending_approvals_returns_instances_for_user()
    {
        $order = PurchaseOrder::factory()->create([
            'status' => OrderStatus::DRAFT,
        ]);

        $workflow = Workflow::create([
            'name' => '采购审批流程',
            'code' => 'purchase_order_approval',
            'type' => 'purchase_order',
            'description' => '测试用采购审批流程',
            'is_active' => true,
            'version' => 1,
            'created_by' => $this->user->id,
        ]);

        $node = WorkflowNode::create([
            'workflow_id' => $workflow->id,
            'node_name' => '审批',
            'node_type' => 'approval',
            'sequence' => 1,
            'approval_type' => 'single',
            'approver_config' => [
                'user_ids' => [$this->user->id],
            ],
            'next_nodes' => [],
        ]);

        $instance = WorkflowInstance::create([
            'workflow_id' => $workflow->id,
            'instance_no' => 'WFTEST001',
            'reference_type' => PurchaseOrder::class,
            'reference_id' => $order->id,
            'reference_no' => $order->order_no,
            'status' => WorkflowStatus::PENDING,
            'current_node_id' => $node->id,
            'started_by' => $this->user->id,
            'started_at' => now(),
        ]);

        $pending = $this->approvalService->getPendingApprovals($this->user->id);

        $this->assertCount(1, $pending);
        $this->assertTrue($pending->contains('id', $instance->id));
    }
}

<?php

namespace App\Services;

use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\ProductionMaterialIssue;
use App\Models\ProductionMaterialIssueItem;
use App\Models\ProductionReport;
use App\Models\Bom;
use App\Models\ProcessRoute;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;

class ProductionService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function createPlan($data)
    {
        return DB::transaction(function () use ($data) {
            $plan = ProductionPlan::create([
                'plan_no' => 'PP' . date('YmdHis') . rand(1000, 9999),
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'plan_date' => $data['plan_date'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'draft',
                'warehouse_id' => $data['warehouse_id'],
                'created_by' => auth()->id(),
                'remark' => $data['remark'] ?? null,
            ]);

            foreach ($data['items'] as $itemData) {
                $plan->items()->create([
                    'product_id' => $itemData['product_id'],
                    'bom_id' => $itemData['bom_id'] ?? null,
                    'process_route_id' => $itemData['process_route_id'] ?? null,
                    'planned_quantity' => $itemData['planned_quantity'],
                    'planned_start_date' => $itemData['planned_start_date'],
                    'planned_end_date' => $itemData['planned_end_date'],
                    'priority' => $itemData['priority'] ?? 0,
                    'remark' => $itemData['remark'] ?? null,
                ]);
            }

            return $plan->load(['warehouse', 'items.product', 'items.bom', 'items.processRoute', 'creator']);
        });
    }

    public function approvePlan($planId)
    {
        $plan = ProductionPlan::findOrFail($planId);

        if ($plan->status != 'draft') {
            throw new \Exception('计划单状态不允许审核');
        }

        $plan->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $plan->load(['warehouse', 'items.product', 'items.bom', 'items.processRoute', 'approver']);
    }

    public function createWorkOrder($data)
    {
        return DB::transaction(function () use ($data) {
            $workOrder = WorkOrder::create([
                'work_order_no' => 'WO' . date('YmdHis') . rand(1000, 9999),
                'production_plan_id' => $data['production_plan_id'] ?? null,
                'production_plan_item_id' => $data['production_plan_item_id'] ?? null,
                'product_id' => $data['product_id'],
                'bom_id' => $data['bom_id'] ?? null,
                'process_route_id' => $data['process_route_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'quantity' => $data['quantity'],
                'start_date' => $data['start_date'],
                'planned_end_date' => $data['planned_end_date'],
                'status' => 'draft',
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by' => auth()->id(),
                'remark' => $data['remark'] ?? null,
            ]);

            if ($workOrder->process_route_id) {
                $processRoute = ProcessRoute::with('steps')->find($workOrder->process_route_id);
                if ($processRoute) {
                    foreach ($processRoute->steps as $step) {
                        $workOrder->items()->create([
                            'process_route_step_id' => $step->id,
                            'step_name' => $step->step_name,
                            'sequence' => $step->sequence,
                            'planned_time' => $step->total_time,
                            'planned_start_date' => $workOrder->start_date,
                            'planned_end_date' => $workOrder->planned_end_date,
                            'assigned_to' => $workOrder->assigned_to,
                        ]);
                    }
                }
            }

            return $workOrder->load(['product', 'bom', 'processRoute', 'warehouse', 'items', 'creator']);
        });
    }

    public function approveWorkOrder($workOrderId)
    {
        $workOrder = WorkOrder::findOrFail($workOrderId);

        if ($workOrder->status != 'draft') {
            throw new \Exception('工单状态不允许审核');
        }

        $workOrder->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $workOrder->load(['product', 'bom', 'processRoute', 'warehouse', 'items', 'approver']);
    }

    public function issueMaterial($workOrderId, $items)
    {
        $workOrder = WorkOrder::with('bom.items')->findOrFail($workOrderId);

        if ($workOrder->status != 'approved' && $workOrder->status != 'material_issued') {
            throw new \Exception('工单状态不允许领料');
        }

        return DB::transaction(function () use ($workOrder, $items) {
            $materialIssue = ProductionMaterialIssue::create([
                'issue_no' => 'MI' . date('YmdHis') . rand(1000, 9999),
                'work_order_id' => $workOrder->id,
                'warehouse_id' => $workOrder->warehouse_id,
                'issue_date' => now()->toDateString(),
                'type' => 'issue',
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $itemData) {
                $productId = $itemData['product_id'];
                $quantity = $itemData['quantity'];
                $locationId = $itemData['location_id'] ?? null;

                $inventory = $this->inventoryService->getInventory($productId, $workOrder->warehouse_id, $locationId);

                if (!$inventory || $inventory->available_quantity < $quantity) {
                    $product = \App\Models\Product::find($productId);
                    throw new \Exception("物料 {$product->name} 库存不足");
                }

                $unitCost = $inventory->average_cost;

                $materialIssue->items()->create([
                    'product_id' => $productId,
                    'location_id' => $locationId,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                ]);

                $this->inventoryService->stockOut(
                    $productId,
                    $workOrder->warehouse_id,
                    $quantity,
                    $unitCost,
                    [
                        'location_id' => $locationId,
                        'reference_type' => WorkOrder::class,
                        'reference_id' => $workOrder->id,
                        'reference_no' => $workOrder->work_order_no,
                        'remark' => "生产领料：{$workOrder->work_order_no}",
                    ]
                );
            }

            $materialIssue->update([
                'status' => 'completed',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $workOrder->update(['status' => 'material_issued']);

            return $materialIssue->load(['workOrder', 'warehouse', 'items.product', 'items.location', 'approver']);
        });
    }

    public function returnMaterial($workOrderId, $items)
    {
        $workOrder = WorkOrder::findOrFail($workOrderId);

        if ($workOrder->status == 'completed' || $workOrder->status == 'cancelled') {
            throw new \Exception('工单状态不允许退料');
        }

        return DB::transaction(function () use ($workOrder, $items) {
            $materialReturn = ProductionMaterialIssue::create([
                'issue_no' => 'MR' . date('YmdHis') . rand(1000, 9999),
                'work_order_id' => $workOrder->id,
                'warehouse_id' => $workOrder->warehouse_id,
                'issue_date' => now()->toDateString(),
                'type' => 'return',
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $itemData) {
                $productId = $itemData['product_id'];
                $quantity = $itemData['quantity'];
                $locationId = $itemData['location_id'] ?? null;

                $inventory = $this->inventoryService->getInventory($productId, $workOrder->warehouse_id, $locationId);
                $unitCost = $inventory ? $inventory->average_cost : 0;

                $materialReturn->items()->create([
                    'product_id' => $productId,
                    'location_id' => $locationId,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                ]);

                $this->inventoryService->stockIn(
                    $productId,
                    $workOrder->warehouse_id,
                    $quantity,
                    $unitCost,
                    [
                        'location_id' => $locationId,
                        'reference_type' => WorkOrder::class,
                        'reference_id' => $workOrder->id,
                        'reference_no' => $workOrder->work_order_no,
                        'remark' => "生产退料：{$workOrder->work_order_no}",
                    ]
                );
            }

            $materialReturn->update([
                'status' => 'completed',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return $materialReturn->load(['workOrder', 'warehouse', 'items.product', 'items.location', 'approver']);
        });
    }

    public function reportWork($workOrderId, $data)
    {
        $workOrder = WorkOrder::findOrFail($workOrderId);

        if ($workOrder->status != 'material_issued' && $workOrder->status != 'in_progress') {
            throw new \Exception('工单状态不允许报工');
        }

        return DB::transaction(function () use ($workOrder, $data) {
            $report = ProductionReport::create([
                'report_no' => 'PR' . date('YmdHis') . rand(1000, 9999),
                'work_order_id' => $workOrder->id,
                'work_order_item_id' => $data['work_order_item_id'] ?? null,
                'report_date' => $data['report_date'],
                'quantity' => $data['quantity'],
                'qualified_quantity' => $data['qualified_quantity'] ?? $data['quantity'],
                'defective_quantity' => $data['defective_quantity'] ?? 0,
                'work_hours' => $data['work_hours'] ?? 0,
                'overtime_hours' => $data['overtime_hours'] ?? 0,
                'reported_by' => auth()->id(),
                'remark' => $data['remark'] ?? null,
            ]);

            if ($data['work_order_item_id']) {
                $workOrderItem = WorkOrderItem::find($data['work_order_item_id']);
                if ($workOrderItem) {
                    $workOrderItem->increment('actual_time', $data['work_hours'] ?? 0);
                    if ($workOrderItem->status == 'pending') {
                        $workOrderItem->update([
                            'status' => 'in_progress',
                            'actual_start_date' => $data['report_date'],
                        ]);
                    }
                }
            }

            $workOrder->increment('completed_quantity', $data['qualified_quantity'] ?? $data['quantity']);
            $workOrder->updateStatus();

            if ($workOrder->status == 'in_progress') {
                foreach ($workOrder->items as $item) {
                    if ($item->status == 'pending') {
                        $item->update(['status' => 'in_progress']);
                    }
                }
            }

            return $report->load(['workOrder', 'workOrderItem', 'reporter']);
        });
    }

    public function completeWorkOrder($workOrderId)
    {
        $workOrder = WorkOrder::findOrFail($workOrderId);

        if ($workOrder->status != 'in_progress') {
            throw new \Exception('工单状态不允许完成');
        }

        return DB::transaction(function () use ($workOrder) {
            $totalQuantity = $workOrder->quantity;
            $completedQuantity = $workOrder->completed_quantity;

            if ($completedQuantity < $totalQuantity) {
                throw new \Exception('完成数量未达到计划数量');
            }

            foreach ($workOrder->items as $item) {
                if ($item->status != 'completed') {
                    $item->update([
                        'status' => 'completed',
                        'actual_end_date' => now()->toDateString(),
                    ]);
                }
            }

            $workOrder->update([
                'status' => 'completed',
                'actual_end_date' => now()->toDateString(),
            ]);

            $this->inventoryService->stockIn(
                $workOrder->product_id,
                $workOrder->warehouse_id,
                $completedQuantity,
                0,
                [
                    'reference_type' => WorkOrder::class,
                    'reference_id' => $workOrder->id,
                    'reference_no' => $workOrder->work_order_no,
                    'remark' => "生产入库：{$workOrder->work_order_no}",
                ]
            );

            if ($workOrder->production_plan_item_id) {
                $planItem = ProductionPlanItem::find($workOrder->production_plan_item_id);
                if ($planItem) {
                    $planItem->increment('completed_quantity', $completedQuantity);
                    $planItem->productionPlan->updateStatus();
                }
            }

            return $workOrder->load(['product', 'warehouse', 'items']);
        });
    }
}


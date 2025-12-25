<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Workflow;
use App\Constants\OrderStatus;
use App\Services\InventoryService;
use App\Services\ApprovalService;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    protected $inventoryService;
    protected $approvalService;

    public function __construct(InventoryService $inventoryService, ApprovalService $approvalService)
    {
        $this->inventoryService = $inventoryService;
        $this->approvalService = $approvalService;
    }

    public function createOrder($data)
    {
        return DB::transaction(function () use ($data) {
            $order = PurchaseOrder::create([
                'order_no' => 'PO' . date('YmdHis') . rand(1000, 9999),
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'status' => OrderStatus::DRAFT,
                'currency_id' => $data['currency_id'] ?? null,
                'created_by' => auth()->id(),
                'remark' => $data['remark'] ?? null,
            ]);

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($data['items'] as $itemData) {
                $item = $order->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'tax_rate' => $itemData['tax_rate'] ?? 0,
                    'discount_rate' => $itemData['discount_rate'] ?? 0,
                    'remark' => $itemData['remark'] ?? null,
                ]);

                $subtotal += $item->subtotal;
                $taxAmount += $item->tax_amount;
            }

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total_amount' => $subtotal + $taxAmount - ($data['discount_amount'] ?? 0),
            ]);

            return $order->load(['supplier', 'warehouse', 'items.product']);
        });
    }

    public function updateOrder($orderId, $data)
    {
        $order = PurchaseOrder::findOrFail($orderId);

        if ($order->status != OrderStatus::DRAFT) {
            throw new \Exception('只能修改草稿状态的订单');
        }

        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'supplier_id' => $data['supplier_id'] ?? $order->supplier_id,
                'warehouse_id' => $data['warehouse_id'] ?? $order->warehouse_id,
                'order_date' => $data['order_date'] ?? $order->order_date,
                'expected_date' => $data['expected_date'] ?? $order->expected_date,
                'currency_id' => $data['currency_id'] ?? $order->currency_id,
                'remark' => $data['remark'] ?? $order->remark,
            ]);

            if (isset($data['items'])) {
                $order->items()->delete();

                $subtotal = 0;
                $taxAmount = 0;

                foreach ($data['items'] as $itemData) {
                    $item = $order->items()->create([
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'tax_rate' => $itemData['tax_rate'] ?? 0,
                        'discount_rate' => $itemData['discount_rate'] ?? 0,
                        'remark' => $itemData['remark'] ?? null,
                    ]);

                    $subtotal += $item->subtotal;
                    $taxAmount += $item->tax_amount;
                }

                $order->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $data['discount_amount'] ?? 0,
                    'total_amount' => $subtotal + $taxAmount - ($data['discount_amount'] ?? 0),
                ]);
            }

            return $order->load(['supplier', 'warehouse', 'items.product']);
        });
    }

    /**
     * 提交订单审核（将草稿状态转为待审核）
     */
    public function submitForApproval($orderId)
    {
        $order = PurchaseOrder::findOrFail($orderId);

        if ($order->status != OrderStatus::DRAFT) {
            throw new \Exception('只能提交草稿状态的订单');
        }

        return DB::transaction(function () use ($order) {
            // 查找是否有启用的采购订单审批流程
            $workflow = Workflow::where('type', 'purchase_order')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                // 如果有工作流，启动审批流程
                try {
                    $this->approvalService->startWorkflow(
                        $workflow->id,
                        PurchaseOrder::class,
                        $order->id,
                        $order->order_no
                    );
                    // 启动工作流后，状态转为 pending，等待工作流审批完成
                    // 工作流审批完成后会自动更新订单状态为 approved
                    $order->update(['status' => OrderStatus::PENDING]);
                } catch (\Exception $e) {
                    // 如果启动工作流失败，直接转为 pending 状态
                    $order->update(['status' => OrderStatus::PENDING]);
                }
            } else {
                // 如果没有工作流，直接转为 pending 状态
                $order->update(['status' => OrderStatus::PENDING]);
            }

            return $order->load(['supplier', 'warehouse', 'items.product']);
        });
    }

    /**
     * 审批订单（直接审批，不通过工作流）
     */
    public function approveOrder($orderId)
    {
        $order = PurchaseOrder::findOrFail($orderId);

        if ($order->status != OrderStatus::DRAFT && $order->status != OrderStatus::PENDING) {
            throw new \Exception('订单状态不允许审核');
        }

        $order->update([
            'status' => OrderStatus::APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $order->load(['supplier', 'warehouse', 'items.product', 'approver']);
    }

    public function receiveGoods($orderId, $items)
    {
        $order = PurchaseOrder::findOrFail($orderId);

        if ($order->status != OrderStatus::APPROVED && $order->status != OrderStatus::PARTIAL) {
            throw new \Exception('订单状态不允许收货');
        }

        return DB::transaction(function () use ($order, $items) {
            foreach ($items as $itemData) {
                $item = $order->items()->findOrFail($itemData['item_id']);
                $receiveQuantity = $itemData['quantity'];

                if ($item->received_quantity + $receiveQuantity > $item->quantity) {
                    throw new \Exception("商品 {$item->product->name} 收货数量超过订单数量");
                }

                $item->increment('received_quantity', $receiveQuantity);

                $this->inventoryService->stockIn(
                    $item->product_id,
                    $order->warehouse_id,
                    $receiveQuantity,
                    $item->unit_price,
                    [
                        'reference_type' => PurchaseOrder::class,
                        'reference_id' => $order->id,
                        'reference_no' => $order->order_no,
                        'remark' => "采购入库：{$order->order_no}",
                    ]
                );
            }

            $order->updateStatus();

            return $order->load(['supplier', 'warehouse', 'items.product']);
        });
    }

    public function createReturn($data)
    {
        return DB::transaction(function () use ($data) {
            $return = PurchaseReturn::create([
                'return_no' => 'PR' . date('YmdHis') . rand(1000, 9999),
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'return_date' => $data['return_date'],
                'status' => OrderStatus::DRAFT,
                'currency_id' => $data['currency_id'] ?? null,
                'created_by' => auth()->id(),
                'remark' => $data['remark'] ?? null,
            ]);

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($data['items'] as $itemData) {
                $item = $return->items()->create([
                    'purchase_order_item_id' => $itemData['purchase_order_item_id'] ?? null,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'tax_rate' => $itemData['tax_rate'] ?? 0,
                    'remark' => $itemData['remark'] ?? null,
                ]);

                $subtotal += $item->subtotal;
                $taxAmount += $item->tax_amount;
            }

            $return->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
            ]);

            return $return->load(['supplier', 'warehouse', 'items.product']);
        });
    }

    public function updateReturn($returnId, $data)
    {
        $return = PurchaseReturn::findOrFail($returnId);

        if ($return->status != OrderStatus::DRAFT) {
            throw new \Exception('只能修改草稿状态的退货单');
        }

        return DB::transaction(function () use ($return, $data) {
            $return->update([
                'purchase_order_id' => $data['purchase_order_id'] ?? $return->purchase_order_id,
                'supplier_id' => $data['supplier_id'] ?? $return->supplier_id,
                'warehouse_id' => $data['warehouse_id'] ?? $return->warehouse_id,
                'return_date' => $data['return_date'] ?? $return->return_date,
                'currency_id' => $data['currency_id'] ?? $return->currency_id,
                'remark' => $data['remark'] ?? $return->remark,
            ]);

            if (isset($data['items'])) {
                $return->items()->delete();

                $subtotal = 0;
                $taxAmount = 0;

                foreach ($data['items'] as $itemData) {
                    $item = $return->items()->create([
                        'purchase_order_item_id' => $itemData['purchase_order_item_id'] ?? null,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'tax_rate' => $itemData['tax_rate'] ?? 0,
                        'remark' => $itemData['remark'] ?? null,
                    ]);

                    $subtotal += $item->subtotal;
                    $taxAmount += $item->tax_amount;
                }

                $return->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $subtotal + $taxAmount,
                ]);
            }

            return $return->load(['supplier', 'warehouse', 'items.product']);
        });
    }

    /**
     * 提交退货单审核（将草稿状态转为待审核）
     */
    public function submitReturnForApproval($returnId)
    {
        $return = PurchaseReturn::findOrFail($returnId);

        if ($return->status != OrderStatus::DRAFT) {
            throw new \Exception('只能提交草稿状态的退货单');
        }

        return DB::transaction(function () use ($return) {
            // 查找是否有启用的采购退货审批流程
            $workflow = Workflow::where('type', 'purchase_return')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                // 如果有工作流，启动审批流程
                try {
                    $this->approvalService->startWorkflow(
                        $workflow->id,
                        PurchaseReturn::class,
                        $return->id,
                        $return->return_no
                    );
                    // 启动工作流后，状态转为 pending，等待工作流审批完成
                    // 工作流审批完成后会自动更新退货单状态为 approved
                    $return->update(['status' => OrderStatus::PENDING]);
                } catch (\Exception $e) {
                    // 如果启动工作流失败，直接转为 pending 状态
                    $return->update(['status' => OrderStatus::PENDING]);
                }
            } else {
                // 如果没有工作流，直接转为 pending 状态
                $return->update(['status' => OrderStatus::PENDING]);
            }

            return $return->load(['supplier', 'warehouse', 'items.product']);
        });
    }

    /**
     * 审批退货单（直接审批，不通过工作流）
     */
    public function approveReturn($returnId)
    {
        $return = PurchaseReturn::findOrFail($returnId);

        if ($return->status != OrderStatus::DRAFT && $return->status != OrderStatus::PENDING) {
            throw new \Exception('退货单状态不允许审核');
        }

        return DB::transaction(function () use ($return) {
            foreach ($return->items as $item) {
                $this->inventoryService->stockOut(
                    $item->product_id,
                    $return->warehouse_id,
                    $item->quantity,
                    $item->unit_price,
                    [
                        'reference_type' => PurchaseReturn::class,
                        'reference_id' => $return->id,
                        'reference_no' => $return->return_no,
                        'remark' => "采购退货：{$return->return_no}",
                    ]
                );
            }

            $return->update([
                'status' => OrderStatus::COMPLETED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return $return->load(['supplier', 'warehouse', 'items.product', 'approver']);
        });
    }

    public function cancelOrder($orderId)
    {
        $order = PurchaseOrder::findOrFail($orderId);

        if ($order->status == OrderStatus::COMPLETED) {
            throw new \Exception('已完成的订单不能取消');
        }

        $order->update(['status' => OrderStatus::CANCELLED]);

        return $order;
    }

    public function createSettlement($data)
    {
        return DB::transaction(function () use ($data) {
            $settlement = PurchaseSettlement::create([
                'settlement_no' => 'PS' . date('YmdHis') . rand(1000, 9999),
                'supplier_id' => $data['supplier_id'],
                'settlement_date' => $data['settlement_date'],
                'status' => OrderStatus::DRAFT,
                'currency_id' => $data['currency_id'] ?? null,
                'created_by' => auth()->id(),
                'remark' => $data['remark'] ?? null,
            ]);

            $totalAmount = 0;

            foreach ($data['items'] as $itemData) {
                $referenceType = $itemData['reference_type'];
                $referenceId = $itemData['reference_id'];

                if ($referenceType == 'purchase_order') {
                    $reference = PurchaseOrder::findOrFail($referenceId);
                    $referenceNo = $reference->order_no;
                    $amount = $reference->total_amount;
                } elseif ($referenceType == 'purchase_return') {
                    $reference = PurchaseReturn::findOrFail($referenceId);
                    $referenceNo = $reference->return_no;
                    $amount = -$reference->total_amount;
                } else {
                    throw new \Exception('无效的关联类型');
                }

                $settlement->items()->create([
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'reference_no' => $referenceNo,
                    'amount' => $amount,
                    'remark' => $itemData['remark'] ?? null,
                ]);

                $totalAmount += $amount;
            }

            $settlement->update([
                'total_amount' => $totalAmount,
                'remaining_amount' => $totalAmount,
            ]);

            return $settlement->load(['supplier', 'currency', 'items']);
        });
    }

    public function approveSettlement($settlementId)
    {
        $settlement = PurchaseSettlement::findOrFail($settlementId);

        if ($settlement->status != OrderStatus::DRAFT && $settlement->status != OrderStatus::PENDING) {
            throw new \Exception('结算单状态不允许审核');
        }

        $settlement->update([
            'status' => OrderStatus::APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $settlement->load(['supplier', 'currency', 'items', 'approver']);
    }

    public function paySettlement($settlementId, $paidAmount)
    {
        $settlement = PurchaseSettlement::findOrFail($settlementId);

        if ($settlement->status != OrderStatus::APPROVED) {
            throw new \Exception('只能支付已审核的结算单');
        }

        $newPaidAmount = $settlement->paid_amount + $paidAmount;

        if ($newPaidAmount > $settlement->total_amount) {
            throw new \Exception('支付金额不能超过结算总金额');
        }

        // 结算单状态：paid = 4 (根据迁移文件)
        $settlement->update([
            'paid_amount' => $newPaidAmount,
            'remaining_amount' => $settlement->total_amount - $newPaidAmount,
            'status' => $newPaidAmount >= $settlement->total_amount ? 4 : OrderStatus::APPROVED,
        ]);

        return $settlement->load(['supplier', 'currency', 'items']);
    }
}


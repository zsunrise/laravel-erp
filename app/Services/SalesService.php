<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\SalesSettlement;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;

class SalesService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function createOrder($data)
    {
        return DB::transaction(function () use ($data) {
            $order = SalesOrder::create([
                'order_no' => 'SO' . date('YmdHis') . rand(1000, 9999),
                'customer_id' => $data['customer_id'],
                'warehouse_id' => $data['warehouse_id'],
                'order_date' => $data['order_date'],
                'delivery_date' => $data['delivery_date'] ?? null,
                'status' => 'draft',
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

            return $order->load(['customer', 'warehouse', 'items.product']);
        });
    }

    public function updateOrder($orderId, $data)
    {
        $order = SalesOrder::findOrFail($orderId);

        if ($order->status != 'draft') {
            throw new \Exception('只能修改草稿状态的订单');
        }

        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'customer_id' => $data['customer_id'] ?? $order->customer_id,
                'warehouse_id' => $data['warehouse_id'] ?? $order->warehouse_id,
                'order_date' => $data['order_date'] ?? $order->order_date,
                'delivery_date' => $data['delivery_date'] ?? $order->delivery_date,
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

            return $order->load(['customer', 'warehouse', 'items.product']);
        });
    }

    public function approveOrder($orderId)
    {
        $order = SalesOrder::findOrFail($orderId);

        if ($order->status != 'draft' && $order->status != 'pending') {
            throw new \Exception('订单状态不允许审核');
        }

        $order->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $order->load(['customer', 'warehouse', 'items.product', 'approver']);
    }

    public function shipGoods($orderId, $items)
    {
        $order = SalesOrder::findOrFail($orderId);

        if ($order->status != 'approved' && $order->status != 'partial') {
            throw new \Exception('订单状态不允许发货');
        }

        return DB::transaction(function () use ($order, $items) {
            foreach ($items as $itemData) {
                $item = $order->items()->findOrFail($itemData['item_id']);
                $shipQuantity = $itemData['quantity'];

                if ($item->shipped_quantity + $shipQuantity > $item->quantity) {
                    throw new \Exception("商品 {$item->product->name} 发货数量超过订单数量");
                }

                $inventory = $this->inventoryService->getInventory(
                    $item->product_id,
                    $order->warehouse_id
                );

                if (!$inventory || $inventory->available_quantity < $shipQuantity) {
                    throw new \Exception("商品 {$item->product->name} 库存不足");
                }

                $item->increment('shipped_quantity', $shipQuantity);

                $this->inventoryService->stockOut(
                    $item->product_id,
                    $order->warehouse_id,
                    $shipQuantity,
                    $inventory->average_cost,
                    [
                        'reference_type' => SalesOrder::class,
                        'reference_id' => $order->id,
                        'reference_no' => $order->order_no,
                        'remark' => "销售出库：{$order->order_no}",
                    ]
                );
            }

            $order->updateStatus();

            return $order->load(['customer', 'warehouse', 'items.product']);
        });
    }

    public function createReturn($data)
    {
        return DB::transaction(function () use ($data) {
            $return = SalesReturn::create([
                'return_no' => 'SR' . date('YmdHis') . rand(1000, 9999),
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'customer_id' => $data['customer_id'],
                'warehouse_id' => $data['warehouse_id'],
                'return_date' => $data['return_date'],
                'status' => 'draft',
                'currency_id' => $data['currency_id'] ?? null,
                'created_by' => auth()->id(),
                'remark' => $data['remark'] ?? null,
            ]);

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($data['items'] as $itemData) {
                $item = $return->items()->create([
                    'sales_order_item_id' => $itemData['sales_order_item_id'] ?? null,
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

            return $return->load(['customer', 'warehouse', 'items.product']);
        });
    }

    public function approveReturn($returnId)
    {
        $return = SalesReturn::findOrFail($returnId);

        if ($return->status != 'draft' && $return->status != 'pending') {
            throw new \Exception('退货单状态不允许审核');
        }

        return DB::transaction(function () use ($return) {
            foreach ($return->items as $item) {
                $inventory = $this->inventoryService->getInventory(
                    $item->product_id,
                    $return->warehouse_id
                );

                $unitCost = $inventory ? $inventory->average_cost : $item->unit_price;

                $this->inventoryService->stockIn(
                    $item->product_id,
                    $return->warehouse_id,
                    $item->quantity,
                    $unitCost,
                    [
                        'reference_type' => SalesReturn::class,
                        'reference_id' => $return->id,
                        'reference_no' => $return->return_no,
                        'remark' => "销售退货：{$return->return_no}",
                    ]
                );
            }

            $return->update([
                'status' => 'completed',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return $return->load(['customer', 'warehouse', 'items.product', 'approver']);
        });
    }

    public function cancelOrder($orderId)
    {
        $order = SalesOrder::findOrFail($orderId);

        if ($order->status == 'completed') {
            throw new \Exception('已完成的订单不能取消');
        }

        $order->update(['status' => 'cancelled']);

        return $order;
    }

    public function createSettlement($data)
    {
        return DB::transaction(function () use ($data) {
            $settlement = SalesSettlement::create([
                'settlement_no' => 'SS' . date('YmdHis') . rand(1000, 9999),
                'customer_id' => $data['customer_id'],
                'settlement_date' => $data['settlement_date'],
                'status' => 'draft',
                'currency_id' => $data['currency_id'] ?? null,
                'created_by' => auth()->id(),
                'remark' => $data['remark'] ?? null,
            ]);

            $totalAmount = 0;

            foreach ($data['items'] as $itemData) {
                $referenceType = $itemData['reference_type'];
                $referenceId = $itemData['reference_id'];

                if ($referenceType == 'sales_order') {
                    $reference = SalesOrder::findOrFail($referenceId);
                    $referenceNo = $reference->order_no;
                    $amount = $reference->total_amount;
                } elseif ($referenceType == 'sales_return') {
                    $reference = SalesReturn::findOrFail($referenceId);
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

            return $settlement->load(['customer', 'currency', 'items']);
        });
    }

    public function approveSettlement($settlementId)
    {
        $settlement = SalesSettlement::findOrFail($settlementId);

        if ($settlement->status != 'draft' && $settlement->status != 'pending') {
            throw new \Exception('结算单状态不允许审核');
        }

        $settlement->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $settlement->load(['customer', 'currency', 'items', 'approver']);
    }

    public function receivePayment($settlementId, $receivedAmount)
    {
        $settlement = SalesSettlement::findOrFail($settlementId);

        if ($settlement->status != 'approved') {
            throw new \Exception('只能收款已审核的结算单');
        }

        $newReceivedAmount = $settlement->received_amount + $receivedAmount;

        if ($newReceivedAmount > $settlement->total_amount) {
            throw new \Exception('收款金额不能超过结算总金额');
        }

        $settlement->update([
            'received_amount' => $newReceivedAmount,
            'remaining_amount' => $settlement->total_amount - $newReceivedAmount,
            'status' => $newReceivedAmount >= $settlement->total_amount ? 'paid' : 'approved',
        ]);

        return $settlement->load(['customer', 'currency', 'items']);
    }
}


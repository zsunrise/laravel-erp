<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function getInventory($productId, $warehouseId, $locationId = null)
    {
        return Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('location_id', $locationId)
            ->first();
    }

    public function getOrCreateInventory($productId, $warehouseId, $locationId = null)
    {
        $inventory = $this->getInventory($productId, $warehouseId, $locationId);
        
        if (!$inventory) {
            $inventory = Inventory::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'quantity' => 0,
                'available_quantity' => 0,
                'reserved_quantity' => 0,
                'average_cost' => 0,
            ]);
        }
        
        return $inventory;
    }

    public function stockIn($productId, $warehouseId, $quantity, $unitCost = 0, $options = [])
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $unitCost, $options) {
            $locationId = $options['location_id'] ?? null;
            $referenceType = $options['reference_type'] ?? null;
            $referenceId = $options['reference_id'] ?? null;
            $referenceNo = $options['reference_no'] ?? null;
            $userId = $options['user_id'] ?? auth()->id();
            $remark = $options['remark'] ?? null;

            $inventory = $this->getOrCreateInventory($productId, $warehouseId, $locationId);
            $inventory->increase($quantity, $unitCost);

            $transaction = InventoryTransaction::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'type' => 'in',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_no' => $referenceNo,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'user_id' => $userId,
                'remark' => $remark,
                'transaction_date' => now(),
            ]);

            return [
                'inventory' => $inventory,
                'transaction' => $transaction,
            ];
        });
    }

    public function stockOut($productId, $warehouseId, $quantity, $unitCost = 0, $options = [])
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $unitCost, $options) {
            $locationId = $options['location_id'] ?? null;
            $referenceType = $options['reference_type'] ?? null;
            $referenceId = $options['reference_id'] ?? null;
            $referenceNo = $options['reference_no'] ?? null;
            $userId = $options['user_id'] ?? auth()->id();
            $remark = $options['remark'] ?? null;

            $inventory = $this->getInventory($productId, $warehouseId, $locationId);
            
            if (!$inventory || $inventory->available_quantity < $quantity) {
                throw new \Exception('库存不足');
            }

            if ($unitCost == 0) {
                $unitCost = $inventory->average_cost;
            }

            $inventory->decrease($quantity);

            $transaction = InventoryTransaction::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'type' => 'out',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_no' => $referenceNo,
                'quantity' => -$quantity,
                'unit_cost' => $unitCost,
                'total_cost' => -$quantity * $unitCost,
                'user_id' => $userId,
                'remark' => $remark,
                'transaction_date' => now(),
            ]);

            return [
                'inventory' => $inventory,
                'transaction' => $transaction,
            ];
        });
    }

    public function transfer($productId, $fromWarehouseId, $toWarehouseId, $quantity, $options = [])
    {
        return DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $quantity, $options) {
            $fromLocationId = $options['from_location_id'] ?? null;
            $toLocationId = $options['to_location_id'] ?? null;
            $referenceNo = $options['reference_no'] ?? null;
            $userId = $options['user_id'] ?? auth()->id();
            $remark = $options['remark'] ?? null;

            $fromInventory = $this->getInventory($productId, $fromWarehouseId, $fromLocationId);
            
            if (!$fromInventory || $fromInventory->available_quantity < $quantity) {
                throw new \Exception('库存不足');
            }

            $unitCost = $fromInventory->average_cost;
            $fromInventory->decrease($quantity);

            $toInventory = $this->getOrCreateInventory($productId, $toWarehouseId, $toLocationId);
            $toInventory->increase($quantity, $unitCost);

            InventoryTransaction::create([
                'product_id' => $productId,
                'warehouse_id' => $fromWarehouseId,
                'location_id' => $fromLocationId,
                'type' => 'transfer',
                'reference_type' => 'transfer',
                'reference_id' => $toWarehouseId,
                'reference_no' => $referenceNo,
                'quantity' => -$quantity,
                'unit_cost' => $unitCost,
                'total_cost' => -$quantity * $unitCost,
                'user_id' => $userId,
                'remark' => $remark . ' (调出)',
                'transaction_date' => now(),
            ]);

            InventoryTransaction::create([
                'product_id' => $productId,
                'warehouse_id' => $toWarehouseId,
                'location_id' => $toLocationId,
                'type' => 'transfer',
                'reference_type' => 'transfer',
                'reference_id' => $fromWarehouseId,
                'reference_no' => $referenceNo,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'user_id' => $userId,
                'remark' => $remark . ' (调入)',
                'transaction_date' => now(),
            ]);

            return [
                'from_inventory' => $fromInventory,
                'to_inventory' => $toInventory,
            ];
        });
    }

    public function adjust($productId, $warehouseId, $quantity, $unitCost = 0, $options = [])
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $unitCost, $options) {
            $locationId = $options['location_id'] ?? null;
            $referenceNo = $options['reference_no'] ?? null;
            $userId = $options['user_id'] ?? auth()->id();
            $remark = $options['remark'] ?? null;

            $inventory = $this->getOrCreateInventory($productId, $warehouseId, $locationId);
            $difference = $quantity - $inventory->quantity;

            if ($difference > 0) {
                $inventory->increase($difference, $unitCost);
            } else {
                $inventory->decrease(abs($difference));
            }

            if ($unitCost == 0) {
                $unitCost = $inventory->average_cost;
            }

            $transaction = InventoryTransaction::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'type' => 'adjust',
                'reference_type' => 'adjust',
                'reference_id' => null,
                'reference_no' => $referenceNo,
                'quantity' => $difference,
                'unit_cost' => $unitCost,
                'total_cost' => $difference * $unitCost,
                'user_id' => $userId,
                'remark' => $remark,
                'transaction_date' => now(),
            ]);

            return [
                'inventory' => $inventory,
                'transaction' => $transaction,
            ];
        });
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'location_id',
        'quantity',
        'available_quantity',
        'reserved_quantity',
        'average_cost',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'available_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'average_cost' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class);
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'product_id', 'product_id')
            ->where('warehouse_id', $this->warehouse_id);
    }

    public function increase($quantity, $unitCost = 0)
    {
        if ($unitCost > 0 && $this->quantity > 0) {
            $oldTotalCost = $this->average_cost * $this->quantity;
            $newTotalCost = $oldTotalCost + ($unitCost * $quantity);
            $this->average_cost = $newTotalCost / ($this->quantity + $quantity);
        } elseif ($unitCost > 0 && $this->quantity == 0) {
            $this->average_cost = $unitCost;
        }
        
        $this->quantity += $quantity;
        $this->available_quantity += $quantity;
        $this->save();
    }

    public function decrease($quantity)
    {
        if ($this->available_quantity < $quantity) {
            throw new \Exception('可用库存不足');
        }
        
        $this->quantity -= $quantity;
        $this->available_quantity -= $quantity;
        $this->save();
    }

    public function reserve($quantity)
    {
        if ($this->available_quantity < $quantity) {
            throw new \Exception('可用库存不足');
        }
        
        $this->available_quantity -= $quantity;
        $this->reserved_quantity += $quantity;
        $this->save();
    }

    public function release($quantity)
    {
        if ($this->reserved_quantity < $quantity) {
            throw new \Exception('预留库存不足');
        }
        
        $this->available_quantity += $quantity;
        $this->reserved_quantity -= $quantity;
        $this->save();
    }
}

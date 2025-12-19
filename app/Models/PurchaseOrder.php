<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_no',
        'supplier_id',
        'warehouse_id',
        'order_date',
        'expected_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency_id',
        'created_by',
        'approved_by',
        'approved_at',
        'remark',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function updateStatus()
    {
        $totalQuantity = $this->items->sum('quantity');
        $receivedQuantity = $this->items->sum('received_quantity');

        if ($receivedQuantity == 0) {
            $this->status = 'approved';
        } elseif ($receivedQuantity < $totalQuantity) {
            $this->status = 'partial';
        } else {
            $this->status = 'completed';
        }

        $this->save();
    }
}

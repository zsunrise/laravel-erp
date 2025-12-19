<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_no',
        'customer_id',
        'warehouse_id',
        'order_date',
        'delivery_date',
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
        'delivery_date' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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
        return $this->hasMany(SalesOrderItem::class);
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
        return $this->hasMany(SalesReturn::class);
    }

    public function updateStatus()
    {
        $totalQuantity = $this->items->sum('quantity');
        $shippedQuantity = $this->items->sum('shipped_quantity');

        if ($shippedQuantity == 0) {
            $this->status = 'approved';
        } elseif ($shippedQuantity < $totalQuantity) {
            $this->status = 'partial';
        } else {
            $this->status = 'completed';
        }

        $this->save();
    }
}

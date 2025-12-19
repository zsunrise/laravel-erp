<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'received_quantity',
        'unit_price',
        'tax_rate',
        'discount_rate',
        'subtotal',
        'tax_amount',
        'total_amount',
        'remark',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->subtotal = $item->quantity * $item->unit_price * (1 - $item->discount_rate / 100);
            $item->tax_amount = $item->subtotal * $item->tax_rate / 100;
            $item->total_amount = $item->subtotal + $item->tax_amount;
        });
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getRemainingQuantityAttribute()
    {
        return $this->quantity - $this->received_quantity;
    }
}

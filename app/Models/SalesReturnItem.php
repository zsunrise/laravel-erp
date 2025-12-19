<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_return_id',
        'sales_order_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'tax_rate',
        'subtotal',
        'tax_amount',
        'total_amount',
        'remark',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->subtotal = $item->quantity * $item->unit_price;
            $item->tax_amount = $item->subtotal * $item->tax_rate / 100;
            $item->total_amount = $item->subtotal + $item->tax_amount;
        });
    }

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

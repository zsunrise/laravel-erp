<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStocktakeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stocktake_id',
        'product_id',
        'location_id',
        'book_quantity',
        'actual_quantity',
        'difference_quantity',
        'unit_cost',
        'difference_amount',
        'remark',
    ];

    protected $casts = [
        'book_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'difference_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'difference_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->difference_quantity = $item->actual_quantity - $item->book_quantity;
            $item->difference_amount = $item->difference_quantity * $item->unit_cost;
        });
    }

    public function stocktake()
    {
        return $this->belongsTo(InventoryStocktake::class, 'stocktake_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class);
    }
}

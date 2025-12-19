<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionMaterialIssueItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_issue_id',
        'product_id',
        'location_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'remark',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total_cost = $item->quantity * $item->unit_cost;
        });
    }

    public function materialIssue()
    {
        return $this->belongsTo(ProductionMaterialIssue::class);
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

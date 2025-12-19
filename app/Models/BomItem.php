<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BomItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bom_id',
        'component_product_id',
        'quantity',
        'unit_id',
        'loss_rate',
        'sequence',
        'position',
        'remark',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'loss_rate' => 'decimal:2',
    ];

    public function bom()
    {
        return $this->belongsTo(Bom::class);
    }

    public function componentProduct()
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getActualQuantityAttribute()
    {
        return $this->quantity * (1 + $this->loss_rate / 100);
    }
}

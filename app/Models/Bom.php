<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'version',
        'effective_date',
        'expiry_date',
        'is_default',
        'is_active',
        'description',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function items()
    {
        return $this->hasMany(BomItem::class)->orderBy('sequence');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalCostAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * ($item->componentProduct->cost_price ?? 0);
        });
    }
}

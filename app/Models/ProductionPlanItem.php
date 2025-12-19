<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionPlanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_plan_id',
        'product_id',
        'bom_id',
        'process_route_id',
        'planned_quantity',
        'completed_quantity',
        'planned_start_date',
        'planned_end_date',
        'priority',
        'remark',
    ];

    protected $casts = [
        'planned_quantity' => 'integer',
        'completed_quantity' => 'integer',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
    ];

    public function productionPlan()
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function bom()
    {
        return $this->belongsTo(Bom::class);
    }

    public function processRoute()
    {
        return $this->belongsTo(ProcessRoute::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function getRemainingQuantityAttribute()
    {
        return $this->planned_quantity - $this->completed_quantity;
    }
}

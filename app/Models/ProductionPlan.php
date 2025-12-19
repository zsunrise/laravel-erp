<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'plan_no',
        'sales_order_id',
        'plan_date',
        'start_date',
        'end_date',
        'status',
        'warehouse_id',
        'created_by',
        'approved_by',
        'approved_at',
        'remark',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(ProductionPlanItem::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function updateStatus()
    {
        $totalQuantity = $this->items->sum('planned_quantity');
        $completedQuantity = $this->items->sum('completed_quantity');

        if ($completedQuantity == 0) {
            $this->status = 'approved';
        } elseif ($completedQuantity < $totalQuantity) {
            $this->status = 'in_progress';
        } else {
            $this->status = 'completed';
        }

        $this->save();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'process_route_step_id',
        'step_name',
        'sequence',
        'status',
        'planned_time',
        'actual_time',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'assigned_to',
        'remark',
    ];

    protected $casts = [
        'planned_time' => 'decimal:2',
        'actual_time' => 'decimal:2',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function processRouteStep()
    {
        return $this->belongsTo(ProcessRouteStep::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reports()
    {
        return $this->hasMany(ProductionReport::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    /**
     * 序列化日期格式
     * date 类型字段返回 Y-m-d 格式，datetime 类型字段返回 Y-m-d H:i:s 格式
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        if ($date instanceof Carbon && $date->format('H:i:s') === '00:00:00') {
            return $date->format('Y-m-d');
        }
        return $date->format('Y-m-d H:i:s');
    }

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

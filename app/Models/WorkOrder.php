<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'work_order_no',
        'production_plan_id',
        'production_plan_item_id',
        'product_id',
        'bom_id',
        'process_route_id',
        'warehouse_id',
        'quantity',
        'completed_quantity',
        'start_date',
        'planned_end_date',
        'actual_end_date',
        'status',
        'assigned_to',
        'created_by',
        'approved_by',
        'approved_at',
        'remark',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'completed_quantity' => 'integer',
        'start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_end_date' => 'date',
        'approved_at' => 'datetime',
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

    public function productionPlan()
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function productionPlanItem()
    {
        return $this->belongsTo(ProductionPlanItem::class);
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

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(WorkOrderItem::class)->orderBy('sequence');
    }

    public function materialIssues()
    {
        return $this->hasMany(ProductionMaterialIssue::class);
    }

    public function reports()
    {
        return $this->hasMany(ProductionReport::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
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
        $totalQuantity = $this->quantity;
        $completedQuantity = $this->completed_quantity;

        if ($completedQuantity == 0) {
            if ($this->status == 'approved') {
                $this->status = 'material_issued';
            }
        } elseif ($completedQuantity < $totalQuantity) {
            $this->status = 'in_progress';
        } else {
            $this->status = 'completed';
            $this->actual_end_date = now();
        }

        $this->save();
    }
}

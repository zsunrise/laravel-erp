<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasStatus;
use App\Constants\ProductionPlanStatus;
use Carbon\Carbon;

class ProductionPlan extends Model
{
    use HasFactory, SoftDeletes, HasStatus;

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
        'status' => 'integer',
    ];

    protected $appends = ['status_text'];

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

    /**
     * 获取状态类名
     */
    protected function getStatusClass()
    {
        return ProductionPlanStatus::class;
    }

    public function updateStatus()
    {
        $totalQuantity = $this->items->sum('planned_quantity');
        $completedQuantity = $this->items->sum('completed_quantity');

        if ($completedQuantity == 0) {
            $this->status = ProductionPlanStatus::APPROVED;
        } elseif ($completedQuantity < $totalQuantity) {
            $this->status = ProductionPlanStatus::IN_PROGRESS;
        } else {
            $this->status = ProductionPlanStatus::COMPLETED;
        }

        $this->save();
    }
}

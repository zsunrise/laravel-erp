<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_no',
        'work_order_id',
        'work_order_item_id',
        'report_date',
        'quantity',
        'qualified_quantity',
        'defective_quantity',
        'work_hours',
        'overtime_hours',
        'reported_by',
        'remark',
    ];

    protected $casts = [
        'report_date' => 'date',
        'quantity' => 'integer',
        'qualified_quantity' => 'integer',
        'defective_quantity' => 'integer',
        'work_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($report) {
            if ($report->qualified_quantity == 0 && $report->quantity > 0) {
                $report->qualified_quantity = $report->quantity - $report->defective_quantity;
            }
        });
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function workOrderItem()
    {
        return $this->belongsTo(WorkOrderItem::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}

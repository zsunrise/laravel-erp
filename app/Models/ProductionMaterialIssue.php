<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ProductionMaterialIssue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'issue_no',
        'work_order_id',
        'warehouse_id',
        'issue_date',
        'type',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'remark',
    ];

    protected $casts = [
        'issue_date' => 'date',
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

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(ProductionMaterialIssueItem::class, 'material_issue_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

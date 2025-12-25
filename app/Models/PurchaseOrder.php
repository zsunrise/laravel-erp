<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasStatus;
use App\Constants\OrderStatus;
use Carbon\Carbon;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, HasStatus;

    protected $fillable = [
        'order_no',
        'supplier_id',
        'warehouse_id',
        'order_date',
        'expected_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency_id',
        'created_by',
        'approved_by',
        'approved_at',
        'remark',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'approved_at' => 'datetime',
        'status' => 'integer',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /**
     * 获取状态类名
     */
    protected function getStatusClass()
    {
        return OrderStatus::class;
    }

    public function updateStatus()
    {
        $totalQuantity = $this->items->sum('quantity');
        $receivedQuantity = $this->items->sum('received_quantity');

        if ($receivedQuantity == 0) {
            $this->status = OrderStatus::APPROVED;
        } elseif ($receivedQuantity < $totalQuantity) {
            $this->status = OrderStatus::PARTIAL;
        } else {
            $this->status = OrderStatus::COMPLETED;
        }

        $this->save();
    }
}

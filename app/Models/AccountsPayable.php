<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class AccountsPayable extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'reference_type',
        'reference_id',
        'reference_no',
        'invoice_date',
        'due_date',
        'original_amount',
        'paid_amount',
        'remaining_amount',
        'age_days',
        'status',
        'currency_id',
        'remark',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'original_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'age_days' => 'integer',
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

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($payable) {
            $payable->remaining_amount = $payable->original_amount - $payable->paid_amount;
            
            if ($payable->remaining_amount <= 0) {
                $payable->status = 'settled';
            } elseif ($payable->paid_amount > 0) {
                $payable->status = 'partial';
            } else {
                $payable->status = 'outstanding';
            }

            if ($payable->due_date && $payable->remaining_amount > 0) {
                $ageDays = now()->diffInDays($payable->due_date);
                $payable->age_days = $ageDays;
                if ($ageDays > 0 && $payable->status != 'settled') {
                    $payable->status = 'overdue';
                }
            }
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}

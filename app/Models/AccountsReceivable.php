<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountsReceivable extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'reference_type',
        'reference_id',
        'reference_no',
        'invoice_date',
        'due_date',
        'original_amount',
        'received_amount',
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
        'received_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'age_days' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($receivable) {
            $receivable->remaining_amount = $receivable->original_amount - $receivable->received_amount;
            
            if ($receivable->remaining_amount <= 0) {
                $receivable->status = 'settled';
            } elseif ($receivable->received_amount > 0) {
                $receivable->status = 'partial';
            } else {
                $receivable->status = 'outstanding';
            }

            if ($receivable->due_date && $receivable->remaining_amount > 0) {
                $ageDays = now()->diffInDays($receivable->due_date);
                $receivable->age_days = $ageDays;
                if ($ageDays > 0 && $receivable->status != 'settled') {
                    $receivable->status = 'overdue';
                }
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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

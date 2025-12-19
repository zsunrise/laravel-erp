<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseSettlement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'settlement_no',
        'supplier_id',
        'settlement_date',
        'status',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'currency_id',
        'created_by',
        'approved_by',
        'approved_at',
        'remark',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($settlement) {
            $settlement->remaining_amount = $settlement->total_amount - $settlement->paid_amount;
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

    public function items()
    {
        return $this->hasMany(PurchaseSettlementItem::class);
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

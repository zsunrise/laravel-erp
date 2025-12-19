<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostAllocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'allocation_no',
        'allocation_date',
        'cost_type',
        'reference_type',
        'reference_id',
        'reference_no',
        'product_id',
        'total_amount',
        'allocated_amount',
        'allocation_method',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'remark',
    ];

    protected $casts = [
        'allocation_date' => 'date',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}

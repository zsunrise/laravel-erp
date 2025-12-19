<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseSettlementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_settlement_id',
        'reference_type',
        'reference_id',
        'reference_no',
        'amount',
        'remark',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function purchaseSettlement()
    {
        return $this->belongsTo(PurchaseSettlement::class);
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}

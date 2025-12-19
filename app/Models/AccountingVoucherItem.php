<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingVoucherItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'account_id',
        'direction',
        'amount',
        'summary',
        'reference_type',
        'reference_id',
        'reference_no',
        'sequence',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function voucher()
    {
        return $this->belongsTo(AccountingVoucher::class, 'voucher_id');
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}

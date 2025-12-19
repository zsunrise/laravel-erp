<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralLedger extends Model
{
    use HasFactory;

    protected $table = 'general_ledger';

    protected $fillable = [
        'account_id',
        'entry_date',
        'voucher_id',
        'voucher_no',
        'summary',
        'debit_amount',
        'credit_amount',
        'balance',
        'reference_type',
        'reference_id',
        'reference_no',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function voucher()
    {
        return $this->belongsTo(AccountingVoucher::class, 'voucher_id');
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}

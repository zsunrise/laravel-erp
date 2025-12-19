<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'type',
        'category',
        'is_detail',
        'level',
        'order',
        'is_active',
        'remark',
    ];

    protected $casts = [
        'is_detail' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id')->orderBy('order');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(GeneralLedger::class, 'account_id');
    }

    public function getDebitBalanceAttribute()
    {
        return $this->ledgerEntries()->sum('debit_amount') - $this->ledgerEntries()->sum('credit_amount');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class AccountingVoucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'voucher_no',
        'voucher_date',
        'type',
        'attachment_count',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
        'remark',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'posted_at' => 'datetime',
        'attachment_count' => 'integer',
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

    public function items()
    {
        return $this->hasMany(AccountingVoucherItem::class, 'voucher_id')->orderBy('sequence');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(GeneralLedger::class, 'voucher_id');
    }

    public function getTotalDebitAttribute()
    {
        return $this->items()->where('direction', 'debit')->sum('amount');
    }

    public function getTotalCreditAttribute()
    {
        return $this->items()->where('direction', 'credit')->sum('amount');
    }

    public function isBalanced()
    {
        return $this->total_debit == $this->total_credit;
    }
}

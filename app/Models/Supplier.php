<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'contact_person',
        'contact_phone',
        'email',
        'region_id',
        'address',
        'tax_number',
        'bank_name',
        'bank_account',
        'rating',
        'credit_limit',
        'payment_days',
        'is_active',
        'remark',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'payment_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}

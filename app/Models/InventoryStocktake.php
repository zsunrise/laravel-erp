<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryStocktake extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'stocktake_no',
        'warehouse_id',
        'stocktake_date',
        'status',
        'created_by',
        'completed_by',
        'completed_at',
        'remark',
    ];

    protected $casts = [
        'stocktake_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryStocktakeItem::class, 'stocktake_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}

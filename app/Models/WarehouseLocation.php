<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'name',
        'code',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'sort' => 'integer',
        'is_active' => 'boolean',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}

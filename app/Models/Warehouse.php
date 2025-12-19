<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'region_id',
        'address',
        'contact_person',
        'contact_phone',
        'is_default',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function locations()
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }
}

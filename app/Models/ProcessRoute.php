<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessRoute extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'version',
        'effective_date',
        'expiry_date',
        'is_default',
        'is_active',
        'description',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function steps()
    {
        return $this->hasMany(ProcessRouteStep::class)->orderBy('sequence');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalTimeAttribute()
    {
        return $this->steps->sum(function ($step) {
            return $step->standard_time + $step->setup_time + $step->queue_time + $step->move_time;
        });
    }
}

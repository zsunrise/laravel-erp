<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessRouteStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'process_route_id',
        'step_name',
        'step_code',
        'sequence',
        'work_center',
        'standard_time',
        'setup_time',
        'queue_time',
        'move_time',
        'description',
        'remark',
    ];

    protected $casts = [
        'standard_time' => 'decimal:2',
        'setup_time' => 'decimal:2',
        'queue_time' => 'decimal:2',
        'move_time' => 'decimal:2',
    ];

    public function processRoute()
    {
        return $this->belongsTo(ProcessRoute::class);
    }

    public function getTotalTimeAttribute()
    {
        return $this->standard_time + $this->setup_time + $this->queue_time + $this->move_time;
    }
}

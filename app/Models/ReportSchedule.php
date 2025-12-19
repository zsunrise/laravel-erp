<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'schedule_type',
        'schedule_time',
        'recipients',
        'is_active',
        'last_run_at',
        'next_run_at',
        'created_by',
    ];

    protected $casts = [
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function report()
    {
        return $this->belongsTo(ReportDefinition::class, 'report_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

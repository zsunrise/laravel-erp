<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportDefinition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'category',
        'description',
        'query_config',
        'display_config',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'query_config' => 'array',
        'display_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function schedules()
    {
        return $this->hasMany(ReportSchedule::class, 'report_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

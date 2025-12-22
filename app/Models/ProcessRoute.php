<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

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

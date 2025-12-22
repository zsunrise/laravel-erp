<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ApprovalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'instance_id',
        'node_id',
        'approver_id',
        'action',
        'status',
        'comment',
        'transferred_to',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
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

    public function instance()
    {
        return $this->belongsTo(WorkflowInstance::class, 'instance_id');
    }

    public function node()
    {
        return $this->belongsTo(WorkflowNode::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function transferredTo()
    {
        return $this->belongsTo(User::class, 'transferred_to');
    }
}

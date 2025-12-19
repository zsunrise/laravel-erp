<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'node_name',
        'node_type',
        'sequence',
        'approval_type',
        'approver_config',
        'condition_config',
        'next_nodes',
        'timeout_hours',
        'is_required',
        'remark',
    ];

    protected $casts = [
        'approver_config' => 'array',
        'condition_config' => 'array',
        'next_nodes' => 'array',
        'is_required' => 'boolean',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function instances()
    {
        return $this->hasMany(WorkflowInstance::class, 'current_node_id');
    }

    public function approvalRecords()
    {
        return $this->hasMany(ApprovalRecord::class);
    }
}

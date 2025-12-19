<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowInstance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workflow_id',
        'instance_no',
        'reference_type',
        'reference_id',
        'reference_no',
        'status',
        'current_node_id',
        'started_by',
        'started_at',
        'completed_at',
        'remark',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function currentNode()
    {
        return $this->belongsTo(WorkflowNode::class, 'current_node_id');
    }

    public function approvalRecords()
    {
        return $this->hasMany(ApprovalRecord::class, 'instance_id');
    }

    public function starter()
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}

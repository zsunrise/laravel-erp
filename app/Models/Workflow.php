<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'is_active',
        'version',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    public function nodes()
    {
        return $this->hasMany(WorkflowNode::class)->orderBy('sequence');
    }

    public function instances()
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

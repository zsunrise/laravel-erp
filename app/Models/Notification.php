<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'content',
        'channel',
        'status',
        'priority',
        'reference_type',
        'reference_id',
        'reference_no',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public function markAsRead()
    {
        if (!$this->read_at) {
            $this->update(['status' => 'read', 'read_at' => now()]);
        }
    }
}

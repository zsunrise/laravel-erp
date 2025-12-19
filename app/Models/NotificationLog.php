<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'template_id',
        'channel',
        'recipient_type',
        'recipient',
        'status',
        'content',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class);
    }
}

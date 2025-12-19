<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'channel',
        'subject',
        'content',
        'variables',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs()
    {
        return $this->hasMany(NotificationLog::class, 'template_id');
    }

    public function render($data = [])
    {
        $content = $this->content;
        $subject = $this->subject;

        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
            if ($subject) {
                $subject = str_replace('{' . $key . '}', $value, $subject);
            }
        }

        return [
            'subject' => $subject,
            'content' => $content,
        ];
    }
}

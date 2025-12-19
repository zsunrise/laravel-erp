<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'module',
        'action',
        'method',
        'path',
        'request_data',
        'response_data',
        'ip',
        'user_agent',
        'status_code',
        'message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

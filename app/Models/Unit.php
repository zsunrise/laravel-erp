<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'symbol',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'sort' => 'integer',
        'is_active' => 'boolean',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingGoal extends Model
{
    protected $fillable = [
        'name',
        'target_amount',
        'current_amount',
        'start_date',
        'deadline',
        'status',
        'note',
    ];

    protected $appends = [
        'progress',
        'remaining',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'start_date' => 'date',
            'deadline' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SavingTransaction::class);
    }

    public function getProgressAttribute(): float
    {
        $targetAmount = (float) $this->target_amount;

        if ($targetAmount <= 0) {
            return 0.0;
        }

        return round(((float) $this->current_amount / $targetAmount) * 100, 2);
    }

    public function getRemainingAttribute(): string
    {
        return number_format(max((float) $this->target_amount - (float) $this->current_amount, 0), 2, '.', '');
    }
}

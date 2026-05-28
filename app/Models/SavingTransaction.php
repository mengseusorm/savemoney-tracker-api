<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingTransaction extends Model
{
    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAW = 'withdraw';

    protected $fillable = [
        'saving_goal_id',
        'type',
        'amount',
        'currency_id',
        'currency_amount',
        'exchange_rate',
        'transaction_date',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'currency_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'transaction_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(SavingGoal::class, 'saving_goal_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function signedAmount(): float
    {
        return $this->type === self::TYPE_WITHDRAW ? -1 * (float) $this->amount : (float) $this->amount;
    }
}

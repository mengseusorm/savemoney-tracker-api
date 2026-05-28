<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'expense_category_id',
        'title',
        'amount',
        'currency_id',
        'currency_amount',
        'exchange_rate',
        'is_daily_expense',
        'daily_amount',
        'daily_currency_amount',
        'daily_days',
        'expense_date',
        'expense_end_date',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'currency_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'is_daily_expense' => 'boolean',
            'daily_amount' => 'decimal:2',
            'daily_currency_amount' => 'decimal:2',
            'daily_days' => 'integer',
            'expense_date' => 'date',
            'expense_end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}

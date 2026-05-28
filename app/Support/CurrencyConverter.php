<?php

namespace App\Support;

use App\Models\Currency;

class CurrencyConverter
{
    /**
     * @return array{amount: string, currency_amount: string, currency_id: int|null, exchange_rate: string}
     */
    public static function amountAttributes(float|int|string $amount, ?int $currencyId): array
    {
        $currencyAmount = (float) $amount;
        $currency = $currencyId ? Currency::where('is_active', true)->find($currencyId) : null;
        $exchangeRate = $currency ? (float) $currency->exchange_rate : 1.0;
        $baseAmount = $currencyAmount * $exchangeRate;

        return [
            'amount' => number_format($baseAmount, 2, '.', ''),
            'currency_amount' => number_format($currencyAmount, 2, '.', ''),
            'currency_id' => $currency?->id,
            'exchange_rate' => number_format($exchangeRate, 6, '.', ''),
        ];
    }

    /**
     * @return array{target_amount: string, target_currency_amount: string, currency_id: int|null, exchange_rate: string}
     */
    public static function targetAttributes(float|int|string $amount, ?int $currencyId): array
    {
        $attributes = self::amountAttributes($amount, $currencyId);

        return [
            'target_amount' => $attributes['amount'],
            'target_currency_amount' => $attributes['currency_amount'],
            'currency_id' => $attributes['currency_id'],
            'exchange_rate' => $attributes['exchange_rate'],
        ];
    }
}

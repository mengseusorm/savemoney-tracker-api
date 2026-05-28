<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Support\CurrencyConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $request->user()
                ->expenses()
                ->with(['category', 'currency'])
                ->latest('expense_date')
                ->latest()
                ->get(),
        ]);
    }

    public function store(ExpenseRequest $request)
    {
        $validated = $request->validated();

        $expense = $request->user()->expenses()->create($this->withCurrencyAmount($validated))->load(['category', 'currency']);

        return response()->json([
            'message' => 'Expense created successfully',
            'data' => $expense,
        ], 201);
    }

    public function show(Request $request, Expense $expense)
    {
        $this->authorizeOwner($request, $expense);

        return response()->json([
            'data' => $expense->load(['category', 'currency']),
        ]);
    }

    public function update(ExpenseRequest $request, Expense $expense)
    {
        $this->authorizeOwner($request, $expense);

        $validated = $request->validated();

        $expense->update($this->withCurrencyAmount($validated, $expense));

        return response()->json([
            'message' => 'Expense updated successfully',
            'data' => $expense->load(['category', 'currency']),
        ]);
    }

    public function destroy(Request $request, Expense $expense)
    {
        $this->authorizeOwner($request, $expense);
        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully',
        ]);
    }

    private function authorizeOwner(Request $request, Expense $expense): void
    {
        abort_unless($expense->user_id === $request->user()->id, 404);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function withCurrencyAmount(array $validated, ?Expense $expense = null): array
    {
        if (
            ! array_key_exists('amount', $validated)
            && ! array_key_exists('currency_id', $validated)
            && ! array_key_exists('is_daily_expense', $validated)
            && ! array_key_exists('expense_date', $validated)
            && ! array_key_exists('expense_end_date', $validated)
        ) {
            return $validated;
        }

        $isDailyExpense = (bool) ($validated['is_daily_expense'] ?? $expense?->is_daily_expense ?? false);
        $attributes = CurrencyConverter::amountAttributes(
            $validated['amount'] ?? $expense?->daily_currency_amount ?? $expense?->currency_amount ?? $expense?->amount ?? 0,
            $validated['currency_id'] ?? $expense?->currency_id
        );

        $date = Carbon::parse($validated['expense_date'] ?? $expense?->expense_date ?? now());
        $endDate = isset($validated['expense_end_date'])
            ? Carbon::parse($validated['expense_end_date'])
            : ($expense?->expense_end_date ? Carbon::parse($expense->expense_end_date) : $date->copy());
        if (! $isDailyExpense) {
            return [
                ...$validated,
                ...$attributes,
                'expense_end_date' => $endDate->toDateString(),
                'is_daily_expense' => false,
                'daily_amount' => null,
                'daily_currency_amount' => null,
                'daily_days' => null,
            ];
        }

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $days = $date->daysInMonth;
        $dailyAmount = (float) $attributes['amount'];
        $dailyCurrencyAmount = (float) $attributes['currency_amount'];

        return [
            ...$validated,
            ...$attributes,
            'amount' => number_format($dailyAmount * $days, 2, '.', ''),
            'currency_amount' => number_format($dailyCurrencyAmount * $days, 2, '.', ''),
            'expense_date' => $startOfMonth->toDateString(),
            'expense_end_date' => $endOfMonth->toDateString(),
            'is_daily_expense' => true,
            'daily_amount' => number_format($dailyAmount, 2, '.', ''),
            'daily_currency_amount' => number_format($dailyCurrencyAmount, 2, '.', ''),
            'daily_days' => $days,
        ];
    }
}

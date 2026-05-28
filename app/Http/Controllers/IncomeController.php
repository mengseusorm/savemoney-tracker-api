<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncomeRequest;
use App\Models\Income;
use App\Support\CurrencyConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $request->user()
                ->incomes()
                ->with(['source', 'currency'])
                ->latest('income_date')
                ->latest()
                ->get(),
        ]);
    }

    public function store(IncomeRequest $request)
    {
        $validated = $request->validated();

        $income = $request->user()->incomes()->create($this->withCurrencyAmount($validated))->load(['source', 'currency']);

        return response()->json([
            'message' => 'Income created successfully',
            'data' => $income,
        ], 201);
    }

    public function show(Request $request, Income $income)
    {
        $this->authorizeOwner($request, $income);

        return response()->json([
            'data' => $income->load(['source', 'currency']),
        ]);
    }

    public function update(IncomeRequest $request, Income $income)
    {
        $this->authorizeOwner($request, $income);

        $validated = $request->validated();

        $income->update($this->withCurrencyAmount($validated, $income));

        return response()->json([
            'message' => 'Income updated successfully',
            'data' => $income->load(['source', 'currency']),
        ]);
    }

    public function destroy(Request $request, Income $income)
    {
        $this->authorizeOwner($request, $income);
        $income->delete();

        return response()->json([
            'message' => 'Income deleted successfully',
        ]);
    }

    private function authorizeOwner(Request $request, Income $income): void
    {
        abort_unless($income->user_id === $request->user()->id, 404);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function withCurrencyAmount(array $validated, ?Income $income = null): array
    {
        if (
            ! array_key_exists('amount', $validated)
            && ! array_key_exists('currency_id', $validated)
            && ! array_key_exists('income_date', $validated)
            && ! array_key_exists('income_end_date', $validated)
        ) {
            return $validated;
        }

        $incomeDate = Carbon::parse($validated['income_date'] ?? $income?->income_date ?? now());
        $incomeEndDate = isset($validated['income_end_date'])
            ? Carbon::parse($validated['income_end_date'])
            : ($income?->income_end_date ? Carbon::parse($income->income_end_date) : $incomeDate->copy());

        if (! array_key_exists('amount', $validated) && ! array_key_exists('currency_id', $validated)) {
            return [
                ...$validated,
                'income_end_date' => $incomeEndDate->toDateString(),
            ];
        }

        return [
            ...$validated,
            'income_end_date' => $incomeEndDate->toDateString(),
            ...CurrencyConverter::amountAttributes(
                $validated['amount'] ?? $income?->currency_amount ?? $income?->amount ?? 0,
                $validated['currency_id'] ?? $income?->currency_id
            ),
        ];
    }
}

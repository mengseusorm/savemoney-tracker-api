<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Support\CurrencyConverter;
use Illuminate\Http\Request;

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
        if (! array_key_exists('amount', $validated) && ! array_key_exists('currency_id', $validated)) {
            return $validated;
        }

        return [
            ...$validated,
            ...CurrencyConverter::amountAttributes(
                $validated['amount'] ?? $expense?->currency_amount ?? $expense?->amount ?? 0,
                $validated['currency_id'] ?? $expense?->currency_id
            ),
        ];
    }
}

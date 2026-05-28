<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavingGoalRequest;
use App\Models\SavingGoal;
use App\Support\CurrencyConverter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SavingGoalController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $request->user()
                ->savingGoals()
                ->with('currency')
                ->withCount('transactions')
                ->latest()
                ->get(),
        ]);
    }

    public function store(SavingGoalRequest $request)
    {
        $validated = $request->validated();

        $goal = $request->user()->savingGoals()->create($this->withTargetCurrencyAmount($validated))->load('currency');

        return response()->json([
            'message' => 'Saving goal created successfully',
            'data' => $goal,
        ], 201);
    }

    public function show(Request $request, SavingGoal $savingGoal)
    {
        $this->authorizeOwner($request, $savingGoal);

        return response()->json([
            'data' => $savingGoal->load(['transactions.currency', 'currency']),
        ]);
    }

    public function update(SavingGoalRequest $request, SavingGoal $savingGoal)
    {
        $this->authorizeOwner($request, $savingGoal);

        $validated = $request->validated();

        $attributes = $this->withTargetCurrencyAmount($validated, $savingGoal);

        if (array_key_exists('target_amount', $attributes) && (float) $attributes['target_amount'] < (float) $savingGoal->current_amount) {
            throw ValidationException::withMessages([
                'target_amount' => ['Target amount cannot be less than the current saved amount.'],
            ]);
        }

        $savingGoal->update($attributes);

        return response()->json([
            'message' => 'Saving goal updated successfully',
            'data' => $savingGoal->load('currency'),
        ]);
    }

    public function destroy(Request $request, SavingGoal $savingGoal)
    {
        $this->authorizeOwner($request, $savingGoal);
        $savingGoal->delete();

        return response()->json([
            'message' => 'Saving goal deleted successfully',
        ]);
    }

    private function authorizeOwner(Request $request, SavingGoal $savingGoal): void
    {
        abort_unless($savingGoal->user_id === $request->user()->id, 404);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function withTargetCurrencyAmount(array $validated, ?SavingGoal $savingGoal = null): array
    {
        if (! array_key_exists('target_amount', $validated) && ! array_key_exists('currency_id', $validated)) {
            return $validated;
        }

        return [
            ...$validated,
            ...CurrencyConverter::targetAttributes(
                $validated['target_amount'] ?? $savingGoal?->target_currency_amount ?? $savingGoal?->target_amount ?? 0,
                $validated['currency_id'] ?? $savingGoal?->currency_id
            ),
        ];
    }
}

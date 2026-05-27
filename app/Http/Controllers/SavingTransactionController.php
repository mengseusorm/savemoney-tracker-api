<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavingTransactionRequest;
use App\Models\SavingGoal;
use App\Models\SavingTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingTransactionController extends Controller
{
    public function index(Request $request, SavingGoal $savingGoal)
    {
        $this->authorizeGoalOwner($request, $savingGoal);

        return response()->json([
            'data' => $savingGoal->transactions()->latest('transaction_date')->latest()->get(),
        ]);
    }

    public function store(SavingTransactionRequest $request, SavingGoal $savingGoal)
    {
        $this->authorizeGoalOwner($request, $savingGoal);

        $validated = $request->validated();

        $transaction = DB::transaction(function () use ($request, $savingGoal, $validated) {
            $projectedCurrent = (float) $savingGoal->current_amount + $this->signedAmount($validated['type'], (float) $validated['amount']);
            $this->assertValidProjectedCurrent($savingGoal, $projectedCurrent);

            $transaction = $request->user()->savingTransactions()->create([
                ...$validated,
                'saving_goal_id' => $savingGoal->id,
            ]);

            $this->updateGoalCurrentAmount($savingGoal, $projectedCurrent);

            return $transaction;
        });

        return response()->json([
            'message' => 'Saving transaction created successfully',
            'data' => $transaction->load('goal'),
        ], 201);
    }

    public function show(Request $request, SavingTransaction $savingTransaction)
    {
        $this->authorizeTransactionOwner($request, $savingTransaction);

        return response()->json([
            'data' => $savingTransaction->load('goal'),
        ]);
    }

    public function update(SavingTransactionRequest $request, SavingTransaction $savingTransaction)
    {
        $this->authorizeTransactionOwner($request, $savingTransaction);

        $validated = $request->validated();

        $transaction = DB::transaction(function () use ($savingTransaction, $validated) {
            $goal = $savingTransaction->goal;
            $type = $validated['type'] ?? $savingTransaction->type;
            $amount = (float) ($validated['amount'] ?? $savingTransaction->amount);
            $projectedCurrent = (float) $goal->current_amount - $savingTransaction->signedAmount() + $this->signedAmount($type, $amount);

            $this->assertValidProjectedCurrent($goal, $projectedCurrent);

            $savingTransaction->update($validated);
            $this->updateGoalCurrentAmount($goal, $projectedCurrent);

            return $savingTransaction;
        });

        return response()->json([
            'message' => 'Saving transaction updated successfully',
            'data' => $transaction->load('goal'),
        ]);
    }

    public function destroy(Request $request, SavingTransaction $savingTransaction)
    {
        $this->authorizeTransactionOwner($request, $savingTransaction);

        DB::transaction(function () use ($savingTransaction) {
            $goal = $savingTransaction->goal;
            $projectedCurrent = (float) $goal->current_amount - $savingTransaction->signedAmount();

            $this->assertValidProjectedCurrent($goal, $projectedCurrent);
            $savingTransaction->delete();
            $this->updateGoalCurrentAmount($goal, $projectedCurrent);
        });

        return response()->json([
            'message' => 'Saving transaction deleted successfully',
        ]);
    }

    private function signedAmount(string $type, float $amount): float
    {
        return $type === SavingTransaction::TYPE_WITHDRAW ? -1 * $amount : $amount;
    }

    private function assertValidProjectedCurrent(SavingGoal $goal, float $projectedCurrent): void
    {
        if ($projectedCurrent < 0) {
            throw ValidationException::withMessages([
                'amount' => ['Withdraw cannot be greater than current saved amount.'],
            ]);
        }

        if ($projectedCurrent > (float) $goal->target_amount) {
            throw ValidationException::withMessages([
                'amount' => ['Saving transaction cannot make current amount greater than target amount.'],
            ]);
        }
    }

    private function updateGoalCurrentAmount(SavingGoal $goal, float $currentAmount): void
    {
        $goal->current_amount = number_format($currentAmount, 2, '.', '');

        if ($goal->status !== 'cancelled') {
            $goal->status = $currentAmount >= (float) $goal->target_amount ? 'completed' : 'active';
        }

        $goal->save();
    }

    private function authorizeGoalOwner(Request $request, SavingGoal $savingGoal): void
    {
        abort_unless($savingGoal->user_id === $request->user()->id, 404);
    }

    private function authorizeTransactionOwner(Request $request, SavingTransaction $savingTransaction): void
    {
        abort_unless($savingTransaction->user_id === $request->user()->id, 404);
    }
}

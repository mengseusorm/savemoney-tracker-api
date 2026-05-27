<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavingGoalRequest;
use App\Models\SavingGoal;
use Illuminate\Http\Request;

class SavingGoalController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $request->user()
                ->savingGoals()
                ->withCount('transactions')
                ->latest()
                ->get(),
        ]);
    }

    public function store(SavingGoalRequest $request)
    {
        $validated = $request->validated();

        $goal = $request->user()->savingGoals()->create($validated);

        return response()->json([
            'message' => 'Saving goal created successfully',
            'data' => $goal,
        ], 201);
    }

    public function show(Request $request, SavingGoal $savingGoal)
    {
        $this->authorizeOwner($request, $savingGoal);

        return response()->json([
            'data' => $savingGoal->load('transactions'),
        ]);
    }

    public function update(SavingGoalRequest $request, SavingGoal $savingGoal)
    {
        $this->authorizeOwner($request, $savingGoal);

        $validated = $request->validated();

        $savingGoal->update($validated);

        return response()->json([
            'message' => 'Saving goal updated successfully',
            'data' => $savingGoal,
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
}

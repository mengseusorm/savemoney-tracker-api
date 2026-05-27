<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncomeRequest;
use App\Models\Income;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $request->user()
                ->incomes()
                ->with('source')
                ->latest('income_date')
                ->latest()
                ->get(),
        ]);
    }

    public function store(IncomeRequest $request)
    {
        $validated = $request->validated();

        $income = $request->user()->incomes()->create($validated)->load('source');

        return response()->json([
            'message' => 'Income created successfully',
            'data' => $income,
        ], 201);
    }

    public function show(Request $request, Income $income)
    {
        $this->authorizeOwner($request, $income);

        return response()->json([
            'data' => $income->load('source'),
        ]);
    }

    public function update(IncomeRequest $request, Income $income)
    {
        $this->authorizeOwner($request, $income);

        $validated = $request->validated();

        $income->update($validated);

        return response()->json([
            'message' => 'Income updated successfully',
            'data' => $income->load('source'),
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
}

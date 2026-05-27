<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $request->user()
                ->expenses()
                ->with('category')
                ->latest('expense_date')
                ->latest()
                ->get(),
        ]);
    }

    public function store(ExpenseRequest $request)
    {
        $validated = $request->validated();

        $expense = $request->user()->expenses()->create($validated)->load('category');

        return response()->json([
            'message' => 'Expense created successfully',
            'data' => $expense,
        ], 201);
    }

    public function show(Request $request, Expense $expense)
    {
        $this->authorizeOwner($request, $expense);

        return response()->json([
            'data' => $expense->load('category'),
        ]);
    }

    public function update(ExpenseRequest $request, Expense $expense)
    {
        $this->authorizeOwner($request, $expense);

        $validated = $request->validated();

        $expense->update($validated);

        return response()->json([
            'message' => 'Expense updated successfully',
            'data' => $expense->load('category'),
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
}

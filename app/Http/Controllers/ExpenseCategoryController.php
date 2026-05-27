<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $request->user()->expenseCategories()->orderBy('name')->get(),
        ]);
    }

    public function store(ExpenseCategoryRequest $request)
    {
        $validated = $request->validated();

        $category = $request->user()->expenseCategories()->create($validated);

        return response()->json([
            'message' => 'Expense category created successfully',
            'data' => $category,
        ], 201);
    }

    public function show(Request $request, ExpenseCategory $expenseCategory)
    {
        $this->authorizeOwner($request, $expenseCategory);

        return response()->json([
            'data' => $expenseCategory,
        ]);
    }

    public function update(ExpenseCategoryRequest $request, ExpenseCategory $expenseCategory)
    {
        $this->authorizeOwner($request, $expenseCategory);

        $validated = $request->validated();

        $expenseCategory->update($validated);

        return response()->json([
            'message' => 'Expense category updated successfully',
            'data' => $expenseCategory,
        ]);
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory)
    {
        $this->authorizeOwner($request, $expenseCategory);

        if ($expenseCategory->expenses()->exists()) {
            return response()->json([
                'message' => 'Expense category is used by expenses and cannot be deleted.',
            ], 409);
        }

        $expenseCategory->delete();

        return response()->json([
            'message' => 'Expense category deleted successfully',
        ]);
    }

    private function authorizeOwner(Request $request, ExpenseCategory $expenseCategory): void
    {
        abort_unless($expenseCategory->user_id === $request->user()->id, 404);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncomeSourceRequest;
use App\Models\IncomeSource;
use Illuminate\Http\Request;

class IncomeSourceController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $request->user()->incomeSources()->orderBy('name')->get(),
        ]);
    }

    public function store(IncomeSourceRequest $request)
    {
        $validated = $request->validated();

        $source = $request->user()->incomeSources()->create($validated);

        return response()->json([
            'message' => 'Income source created successfully',
            'data' => $source,
        ], 201);
    }

    public function show(Request $request, IncomeSource $incomeSource)
    {
        $this->authorizeOwner($request, $incomeSource);

        return response()->json([
            'data' => $incomeSource,
        ]);
    }

    public function update(IncomeSourceRequest $request, IncomeSource $incomeSource)
    {
        $this->authorizeOwner($request, $incomeSource);

        $validated = $request->validated();

        $incomeSource->update($validated);

        return response()->json([
            'message' => 'Income source updated successfully',
            'data' => $incomeSource,
        ]);
    }

    public function destroy(Request $request, IncomeSource $incomeSource)
    {
        $this->authorizeOwner($request, $incomeSource);

        if ($incomeSource->incomes()->exists()) {
            return response()->json([
                'message' => 'Income source is used by incomes and cannot be deleted.',
            ], 409);
        }

        $incomeSource->delete();

        return response()->json([
            'message' => 'Income source deleted successfully',
        ]);
    }

    private function authorizeOwner(Request $request, IncomeSource $incomeSource): void
    {
        abort_unless($incomeSource->user_id === $request->user()->id, 404);
    }
}

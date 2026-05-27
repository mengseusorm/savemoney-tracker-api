<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportSummaryRequest;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function summary(ReportSummaryRequest $request)
    {
        [$fromDate, $toDate] = $this->dateRange($request->validated());

        $incomeQuery = $request->user()->incomes();
        $expenseQuery = $request->user()->expenses();

        if ($fromDate && $toDate) {
            $incomeQuery->whereBetween('income_date', [$fromDate, $toDate]);
            $expenseQuery->whereBetween('expense_date', [$fromDate, $toDate]);
        }

        $totalIncome = (float) $incomeQuery->sum('amount');
        $totalExpense = (float) $expenseQuery->sum('amount');

        $incomeBySource = $request->user()
            ->incomeSources()
            ->withSum(['incomes as total_amount' => function ($query) use ($fromDate, $toDate) {
                if ($fromDate && $toDate) {
                    $query->whereBetween('income_date', [$fromDate, $toDate]);
                }
            }], 'amount')
            ->orderBy('name')
            ->get();

        $expenseByCategory = $request->user()
            ->expenseCategories()
            ->withSum(['expenses as total_amount' => function ($query) use ($fromDate, $toDate) {
                if ($fromDate && $toDate) {
                    $query->whereBetween('expense_date', [$fromDate, $toDate]);
                }
            }], 'amount')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'total_income' => number_format($totalIncome, 2, '.', ''),
                'total_expense' => number_format($totalExpense, 2, '.', ''),
                'balance' => number_format($totalIncome - $totalExpense, 2, '.', ''),
                'income_by_source' => $incomeBySource,
                'expense_by_category' => $expenseByCategory,
            ],
        ]);
    }

    private function dateRange(array $validated): array
    {
        if (isset($validated['month'])) {
            $month = Carbon::createFromFormat('Y-m', $validated['month']);

            return [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ];
        }

        if (isset($validated['from_date']) || isset($validated['to_date'])) {
            $fromDate = $validated['from_date'] ?? $validated['to_date'];
            $toDate = $validated['to_date'] ?? $validated['from_date'];

            return [$fromDate, $toDate];
        }

        return [null, null];
    }
}

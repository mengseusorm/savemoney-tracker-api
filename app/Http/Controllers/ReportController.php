<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportSummaryRequest;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function summary(ReportSummaryRequest $request)
    {
        [$fromDate, $toDate] = $this->dateRange($request->validated());

        $incomeQuery = $request->user()->incomes()->with('source');
        $expenseQuery = $request->user()->expenses()->with('category');

        if ($fromDate && $toDate) {
            $this->applyPeriodDateRange($incomeQuery, 'income_date', 'income_end_date', $fromDate, $toDate);
            $this->applyExpenseDateRange($expenseQuery, $fromDate, $toDate);
        }

        $incomes = $incomeQuery->get();
        $totalIncome = (float) $incomes->sum('amount');
        $expenses = $this->withExpenseReportAmounts($expenseQuery->get(), $fromDate, $toDate);
        $totalExpense = (float) $expenses->sum('report_amount');

        $incomeTotalsBySource = $incomes
            ->groupBy('income_source_id')
            ->map(fn ($items) => number_format((float) $items->sum('amount'), 2, '.', ''));
        $incomeBySource = $request->user()
            ->incomeSources()
            ->orderBy('name')
            ->get()
            ->map(function ($source) use ($incomeTotalsBySource) {
                $source->setAttribute('total_amount', $incomeTotalsBySource->get($source->id, '0.00'));

                return $source;
            });

        $expenseTotalsByCategory = $expenses
            ->groupBy('expense_category_id')
            ->map(fn ($items) => number_format((float) $items->sum('report_amount'), 2, '.', ''));
        $expenseByCategory = $request->user()
            ->expenseCategories()
            ->orderBy('name')
            ->get()
            ->map(function ($category) use ($expenseTotalsByCategory) {
                $category->setAttribute('total_amount', $expenseTotalsByCategory->get($category->id, '0.00'));

                return $category;
            });

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

    public function incomes(ReportSummaryRequest $request)
    {
        [$fromDate, $toDate] = $this->dateRange($request->validated());

        $query = $request->user()
            ->incomes()
            ->with(['source', 'currency'])
            ->latest('income_date')
            ->latest();

        $this->applyPeriodDateRange($query, 'income_date', 'income_end_date', $fromDate, $toDate);

        $rows = $query->get();
        $bySource = $rows
            ->groupBy(fn ($income) => $income->source?->name ?? 'Unknown')
            ->map(fn ($items, $name) => [
                'name' => $name,
                'total_amount' => number_format((float) $items->sum('amount'), 2, '.', ''),
                'count' => $items->count(),
            ])
            ->values();

        return response()->json([
            'data' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'total_amount' => number_format((float) $rows->sum('amount'), 2, '.', ''),
                'count' => $rows->count(),
                'by_source' => $bySource,
                'rows' => $rows,
            ],
        ]);
    }

    public function expenses(ReportSummaryRequest $request)
    {
        [$fromDate, $toDate] = $this->dateRange($request->validated());

        $query = $request->user()
            ->expenses()
            ->with(['category', 'currency'])
            ->latest('expense_date')
            ->latest();

        $this->applyExpenseDateRange($query, $fromDate, $toDate);

        $rows = $this->withExpenseReportAmounts($query->get(), $fromDate, $toDate);
        $byCategory = $rows
            ->groupBy(fn ($expense) => $expense->category?->name ?? 'Unknown')
            ->map(fn ($items, $name) => [
                'name' => $name,
                'total_amount' => number_format((float) $items->sum('report_amount'), 2, '.', ''),
                'count' => $items->count(),
            ])
            ->values();

        return response()->json([
            'data' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'total_amount' => number_format((float) $rows->sum('report_amount'), 2, '.', ''),
                'count' => $rows->count(),
                'by_category' => $byCategory,
                'rows' => $rows,
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

    private function applyExpenseDateRange($query, ?string $fromDate, ?string $toDate): void
    {
        $this->applyPeriodDateRange($query, 'expense_date', 'expense_end_date', $fromDate, $toDate);
    }

    private function applyPeriodDateRange($query, string $startColumn, string $endColumn, ?string $fromDate, ?string $toDate): void
    {
        if (! $fromDate || ! $toDate) {
            return;
        }

        $query
            ->where($startColumn, '<=', $toDate)
            ->where(function ($query) use ($fromDate, $startColumn, $endColumn) {
                $query
                    ->where(function ($query) use ($fromDate, $startColumn, $endColumn) {
                        $query
                            ->whereNull($endColumn)
                            ->where($startColumn, '>=', $fromDate);
                    })
                    ->orWhere($endColumn, '>=', $fromDate);
            });
    }

    private function withExpenseReportAmounts($rows, ?string $fromDate, ?string $toDate)
    {
        return $rows->map(function ($expense) use ($fromDate, $toDate) {
            $days = $this->expenseReportDays($expense, $fromDate, $toDate);
            $reportAmount = $expense->daily_amount !== null
                ? (float) $expense->daily_amount * $days
                : (float) $expense->amount;
            $reportCurrencyAmount = $expense->daily_currency_amount !== null
                ? (float) $expense->daily_currency_amount * $days
                : (float) ($expense->currency_amount ?? $expense->amount);

            $expense->setAttribute('report_amount', number_format($reportAmount, 2, '.', ''));
            $expense->setAttribute('report_currency_amount', number_format($reportCurrencyAmount, 2, '.', ''));
            $expense->setAttribute('report_days', $days);

            return $expense;
        });
    }

    private function expenseReportDays($expense, ?string $fromDate, ?string $toDate): int
    {
        if ($expense->daily_amount === null) {
            return 1;
        }

        $startDate = Carbon::parse($expense->expense_date);
        $endDate = $expense->expense_end_date
            ? Carbon::parse($expense->expense_end_date)
            : $startDate->copy();

        if (! $fromDate || ! $toDate) {
            return (int) ($expense->daily_days ?? ($startDate->diffInDays($endDate) + 1));
        }

        $rangeStart = Carbon::parse($fromDate);
        $rangeEnd = Carbon::parse($toDate);
        $overlapStart = $startDate->greaterThan($rangeStart) ? $startDate : $rangeStart;
        $overlapEnd = $endDate->lessThan($rangeEnd) ? $endDate : $rangeEnd;

        if ($overlapEnd->lessThan($overlapStart)) {
            return 0;
        }

        return (int) $overlapStart->diffInDays($overlapEnd) + 1;
    }
}

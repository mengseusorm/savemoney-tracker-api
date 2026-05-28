<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MoneyTrackerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_income_expense_and_get_summary(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $sourceId = $this->postJson('/api/income-sources', [
            'name' => 'Salary',
            'description' => 'Monthly salary',
        ])->assertCreated()->json('data.id');

        $categoryId = $this->postJson('/api/expense-categories', [
            'name' => 'Food',
            'icon' => 'utensils',
            'color' => '#16a34a',
        ])->assertCreated()->json('data.id');

        $this->postJson('/api/incomes', [
            'income_source_id' => $sourceId,
            'title' => 'May salary',
            'amount' => 500,
            'income_date' => '2026-05-01',
        ])->assertCreated();

        $this->postJson('/api/expenses', [
            'expense_category_id' => $categoryId,
            'title' => 'Lunch',
            'amount' => 25,
            'expense_date' => '2026-05-02',
        ])->assertCreated();

        $this->getJson('/api/reports/summary?month=2026-05')
            ->assertOk()
            ->assertJsonPath('data.total_income', '500.00')
            ->assertJsonPath('data.total_expense', '25.00')
            ->assertJsonPath('data.balance', '475.00')
            ->assertJsonPath('data.income_by_source.0.name', 'Salary')
            ->assertJsonPath('data.expense_by_category.0.name', 'Food');
    }

    public function test_income_amount_is_converted_from_selected_currency(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $currency = Currency::create([
            'code' => 'KHR',
            'name' => 'Cambodian Riel',
            'symbol' => 'KHR',
            'exchange_rate' => 4000,
        ]);
        $sourceId = $this->postJson('/api/income-sources', [
            'name' => 'Freelance',
        ])->assertCreated()->json('data.id');

        $this->postJson('/api/incomes', [
            'income_source_id' => $sourceId,
            'title' => 'Invoice',
            'amount' => 400000,
            'currency_id' => $currency->id,
            'income_date' => '2026-05-01',
        ])
            ->assertCreated()
            ->assertJsonPath('data.amount', '100.00')
            ->assertJsonPath('data.currency_amount', '400000.00')
            ->assertJsonPath('data.exchange_rate', '4000.000000')
            ->assertJsonPath('data.currency.code', 'KHR');
    }

    public function test_user_can_get_income_and_expense_reports_by_date_range(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $sourceId = $this->postJson('/api/income-sources', [
            'name' => 'Salary',
        ])->assertCreated()->json('data.id');
        $categoryId = $this->postJson('/api/expense-categories', [
            'name' => 'Food',
        ])->assertCreated()->json('data.id');

        $this->postJson('/api/incomes', [
            'income_source_id' => $sourceId,
            'title' => 'May salary',
            'amount' => 500,
            'income_date' => '2026-05-01',
        ])->assertCreated();
        $this->postJson('/api/incomes', [
            'income_source_id' => $sourceId,
            'title' => 'June salary',
            'amount' => 700,
            'income_date' => '2026-06-01',
        ])->assertCreated();
        $this->postJson('/api/expenses', [
            'expense_category_id' => $categoryId,
            'title' => 'Lunch',
            'amount' => 20,
            'expense_date' => '2026-05-02',
        ])->assertCreated();

        $this->getJson('/api/reports/incomes?from_date=2026-05-01&to_date=2026-05-31')
            ->assertOk()
            ->assertJsonPath('data.total_amount', '500.00')
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.by_source.0.name', 'Salary');

        $this->getJson('/api/reports/expenses?from_date=2026-05-01&to_date=2026-05-31')
            ->assertOk()
            ->assertJsonPath('data.total_amount', '20.00')
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.by_category.0.name', 'Food');
    }

    public function test_daily_expense_amount_is_calculated_for_full_month_in_reports(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $categoryId = $this->postJson('/api/expense-categories', [
            'name' => 'Transport',
        ])->assertCreated()->json('data.id');

        $this->postJson('/api/expenses', [
            'expense_category_id' => $categoryId,
            'title' => 'Bus',
            'amount' => 2,
            'is_daily_expense' => true,
            'expense_date' => '2026-05-01',
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_daily_expense', true)
            ->assertJsonPath('data.daily_amount', '2.00')
            ->assertJsonPath('data.daily_days', 31)
            ->assertJsonPath('data.expense_date', '2026-05-01T00:00:00.000000Z')
            ->assertJsonPath('data.expense_end_date', '2026-05-31T00:00:00.000000Z')
            ->assertJsonPath('data.amount', '62.00');

        $this->getJson('/api/reports/expenses?from_date=2026-05-01&to_date=2026-05-31')
            ->assertOk()
            ->assertJsonPath('data.total_amount', '62.00')
            ->assertJsonPath('data.rows.0.is_daily_expense', true)
            ->assertJsonPath('data.rows.0.report_days', 31);

        $this->getJson('/api/reports/expenses?from_date=2026-05-01&to_date=2026-05-07')
            ->assertOk()
            ->assertJsonPath('data.total_amount', '14.00')
            ->assertJsonPath('data.rows.0.report_days', 7);
    }

    public function test_normal_expense_date_range_keeps_entered_amount(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $categoryId = $this->postJson('/api/expense-categories', [
            'name' => 'Parking',
        ])->assertCreated()->json('data.id');

        $this->postJson('/api/expenses', [
            'expense_category_id' => $categoryId,
            'title' => 'Parking',
            'amount' => 10,
            'is_daily_expense' => false,
            'expense_date' => '2026-05-01',
            'expense_end_date' => '2026-05-03',
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_daily_expense', false)
            ->assertJsonPath('data.daily_amount', null)
            ->assertJsonPath('data.daily_days', null)
            ->assertJsonPath('data.amount', '10.00');

        $this->getJson('/api/reports/expenses?from_date=2026-05-02&to_date=2026-05-02')
            ->assertOk()
            ->assertJsonPath('data.total_amount', '10.00')
            ->assertJsonPath('data.rows.0.report_days', 1);
    }

    public function test_income_date_range_is_included_when_report_range_overlaps(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $sourceId = $this->postJson('/api/income-sources', [
            'name' => 'Contract',
        ])->assertCreated()->json('data.id');

        $this->postJson('/api/incomes', [
            'income_source_id' => $sourceId,
            'title' => 'Project',
            'amount' => 300,
            'income_date' => '2026-05-01',
            'income_end_date' => '2026-05-31',
        ])
            ->assertCreated()
            ->assertJsonPath('data.income_end_date', '2026-05-31T00:00:00.000000Z')
            ->assertJsonPath('data.amount', '300.00');

        $this->getJson('/api/reports/incomes?from_date=2026-05-15&to_date=2026-05-20')
            ->assertOk()
            ->assertJsonPath('data.total_amount', '300.00')
            ->assertJsonPath('data.count', 1);
    }

    public function test_user_cannot_use_another_users_income_source(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $source = $owner->incomeSources()->create([
            'name' => 'Salary',
        ]);

        Sanctum::actingAs($otherUser);

        $this->postJson('/api/incomes', [
            'income_source_id' => $source->id,
            'title' => 'Wrong source',
            'amount' => 100,
            'income_date' => '2026-05-01',
        ])->assertUnprocessable();
    }

    public function test_saving_transactions_update_goal_current_amount(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $goalId = $this->postJson('/api/saving-goals', [
            'name' => 'Laptop',
            'target_amount' => 900,
            'start_date' => '2026-05-01',
            'deadline' => '2026-12-31',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/saving-goals/{$goalId}/transactions", [
            'type' => 'deposit',
            'amount' => 100,
            'transaction_date' => '2026-05-03',
        ])->assertCreated()->assertJsonPath('data.goal.current_amount', '100.00');

        $this->postJson("/api/saving-goals/{$goalId}/transactions", [
            'type' => 'withdraw',
            'amount' => 40,
            'transaction_date' => '2026-05-04',
        ])->assertCreated()->assertJsonPath('data.goal.current_amount', '60.00');

        $this->getJson("/api/saving-goals/{$goalId}")
            ->assertOk()
            ->assertJsonPath('data.current_amount', '60.00')
            ->assertJsonPath('data.remaining', '840.00')
            ->assertJsonPath('data.progress', 6.67);
    }

    public function test_saving_withdraw_cannot_exceed_current_amount(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $goalId = $this->postJson('/api/saving-goals', [
            'name' => 'Emergency fund',
            'target_amount' => 1000,
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/saving-goals/{$goalId}/transactions", [
            'type' => 'withdraw',
            'amount' => 10,
            'transaction_date' => '2026-05-04',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Withdraw cannot be greater than current saved amount.');
    }

    public function test_saving_deposit_cannot_exceed_target_amount(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $goalId = $this->postJson('/api/saving-goals', [
            'name' => 'Trip',
            'target_amount' => 100,
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/saving-goals/{$goalId}/transactions", [
            'type' => 'deposit',
            'amount' => 101,
            'transaction_date' => '2026-05-04',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Saving transaction cannot make current amount greater than target amount.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
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

<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\User;
use App\Support\ViewMonth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecurringManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_legacy_recurring_url(): void
    {
        $response = $this->get(route('recurring.index'));

        $response->assertRedirect();
    }

    public function test_legacy_recurring_url_redirects_to_records(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('recurring.index'));

        $response->assertRedirect(route('records.index', ViewMonth::queryParams(now()->year, now()->month)));
    }

    public function test_user_can_create_recurring_expense_from_dashboard_checkbox(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('dashboard'))->post(route('expenses.store'), [
            'name' => 'Streaming',
            'amount' => '9.99',
            'category' => 'Entertainment & Subscriptions',
            'date' => '2026-06-12',
            'recurring' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('recurring_expenses', [
            'user_id' => $user->id,
            'name' => 'Streaming',
            'day_of_month' => 12,
        ]);
        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'name' => 'Streaming',
            'date' => '2026-06-12',
        ]);
    }

    public function test_user_can_create_recurring_income_from_dashboard_checkbox(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('dashboard'))->post(route('income.store'), [
            'income_name' => 'Part-time pay',
            'income_amount' => '250.00',
            'income_date' => '2026-07-20',
            'income_recurring' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('recurring_incomes', [
            'user_id' => $user->id,
            'name' => 'Part-time pay',
            'day_of_month' => 20,
        ]);
        $this->assertDatabaseHas('incomes', [
            'user_id' => $user->id,
            'name' => 'Part-time pay',
            'date' => '2026-07-20',
        ]);
    }

    public function test_unchecked_expense_does_not_create_recurring_rule(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('expenses.store'), [
            'name' => 'One off',
            'amount' => '5.00',
            'category' => 'Food',
            'date' => now()->toDateString(),
        ]);

        $this->assertSame(0, RecurringExpense::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, Expense::query()->where('user_id', $user->id)->count());
    }

    public function test_user_can_stop_recurring_by_editing_expense_and_unchecking(): void
    {
        $user = User::factory()->create();
        $rule = RecurringExpense::factory()->for($user)->create();
        $expense = Expense::factory()->for($user)->create([
            'recurring_expense_id' => $rule->id,
        ]);

        $response = $this->actingAs($user)->patch(route('expenses.update', $expense), [
            'name' => $expense->name,
            'amount' => $expense->amount,
            'category' => $expense->category,
            'date' => Carbon::parse($expense->date)->toDateString(),
        ]);

        $response->assertRedirect(route('records.index'));
        $this->assertDatabaseMissing('recurring_expenses', ['id' => $rule->id]);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'recurring_expense_id' => null,
        ]);
    }

    public function test_user_cannot_stop_another_users_recurring_from_expense(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $rule = RecurringExpense::factory()->for($owner)->create();
        $expense = Expense::factory()->for($owner)->create([
            'recurring_expense_id' => $rule->id,
        ]);

        $response = $this->actingAs($other)->patch(route('expenses.update', $expense), [
            'name' => $expense->name,
            'amount' => $expense->amount,
            'category' => $expense->category,
            'date' => Carbon::parse($expense->date)->toDateString(),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('recurring_expenses', ['id' => $rule->id]);
    }

    public function test_recurring_expense_still_validates_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'name' => 'Bad',
            'amount' => '10',
            'category' => 'Not a real category',
            'date' => now()->toDateString(),
            'recurring' => '1',
        ]);

        $response->assertSessionHasErrors('category');
        $this->assertDatabaseCount('recurring_expenses', 0);
    }

    public function test_user_can_update_recurring_expense_from_records_edit_and_syncs_linked_rows(): void
    {
        $user = User::factory()->create();
        $rule = RecurringExpense::factory()->for($user)->create([
            'name' => 'Rent',
            'amount' => 400,
            'category' => 'Housing & Utilities',
            'day_of_month' => 1,
            'starts_on' => '2026-01-01',
        ]);
        $expense = Expense::factory()->for($user)->create([
            'recurring_expense_id' => $rule->id,
            'name' => 'Rent',
            'amount' => 400,
            'category' => 'Housing & Utilities',
            'date' => '2026-02-01',
        ]);

        $response = $this->actingAs($user)->patch(route('expenses.update', $expense), [
            'name' => 'Rent updated',
            'amount' => '450.00',
            'category' => 'Housing & Utilities',
            'date' => '2026-02-03',
            'recurring' => '1',
        ]);

        $response->assertRedirect(route('records.index'));
        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'recurring_expense_id' => $rule->id,
            'name' => 'Rent updated',
            'amount' => '450.00',
            'date' => '2026-02-03',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_recurring_page(): void
    {
        $response = $this->get(route('recurring.index'));

        $response->assertRedirect();
    }

    public function test_authenticated_user_can_view_recurring_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('recurring.index'));

        $response->assertOk();
        $response->assertSeeText('Monthly repeats');
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

    public function test_user_can_delete_own_recurring_expense_rule(): void
    {
        $user = User::factory()->create();
        $rule = RecurringExpense::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('recurring.expenses.destroy', $rule));

        $response->assertRedirect(route('recurring.index'));
        $this->assertDatabaseMissing('recurring_expenses', ['id' => $rule->id]);
    }

    public function test_user_cannot_delete_another_users_recurring_rule(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $rule = RecurringExpense::factory()->for($owner)->create();

        $response = $this->actingAs($other)->delete(route('recurring.expenses.destroy', $rule));

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
}

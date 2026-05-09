<?php

namespace Tests\Feature;

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
        $response->assertSeeText('Recurring payments');
    }

    public function test_user_can_create_recurring_expense_rule(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('recurring.expenses.store'), [
            'name' => 'Streaming',
            'amount' => '9.99',
            'category' => 'Entertainment & Subscriptions',
            'day_of_month' => 12,
            'starts_on' => '2026-01-01',
            'ends_on' => null,
        ]);

        $response->assertRedirect(route('recurring.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('recurring_expenses', [
            'user_id' => $user->id,
            'name' => 'Streaming',
            'day_of_month' => 12,
        ]);
    }

    public function test_user_can_create_recurring_income_rule(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('recurring.income.store'), [
            'income_name' => 'Part-time pay',
            'income_amount' => '250.00',
            'income_day_of_month' => 20,
            'income_starts_on' => '2026-02-01',
            'income_ends_on' => null,
        ]);

        $response->assertRedirect(route('recurring.index'));
        $this->assertDatabaseHas('recurring_incomes', [
            'user_id' => $user->id,
            'name' => 'Part-time pay',
            'day_of_month' => 20,
        ]);
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

    public function test_recurring_expense_validation_rejects_invalid_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('recurring.expenses.store'), [
            'name' => 'Bad',
            'amount' => '10',
            'category' => 'Not a real category',
            'day_of_month' => 1,
            'starts_on' => '2026-01-01',
        ]);

        $response->assertSessionHasErrors('category');
    }
}

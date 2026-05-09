<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_expense_edit_form(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->for($user)->create();

        $response = $this->get(route('expenses.edit', $expense));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_update_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->for($user)->create();

        $response = $this->patch(route('expenses.update', $expense), [
            'name' => 'Hacked',
            'amount' => '99.00',
            'category' => 'Food',
            'date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('expenses', [
            'id' => $expense->id,
            'name' => 'Hacked',
        ]);
    }

    public function test_user_can_view_edit_form_for_own_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->for($user)->create([
            'name' => 'Coffee shop',
            'amount' => '4.50',
            'category' => 'Food',
            'date' => now()->toDateString(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('expenses.edit', $expense));

        $response->assertOk();
        $response->assertSeeText('Edit expense');
        $response->assertSee('value="Coffee shop"', false);
        $response->assertSee('name="name"', false);
    }

    public function test_user_cannot_view_edit_form_for_another_users_expense(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $expense = Expense::factory()->for($owner)->create();

        $response = $this
            ->actingAs($other)
            ->get(route('expenses.edit', $expense));

        $response->assertForbidden();
    }

    public function test_user_can_update_own_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->for($user)->create([
            'name' => 'Old label',
            'amount' => '10.00',
            'category' => 'Food',
            'date' => now()->subDay()->toDateString(),
        ]);

        $newDate = now()->toDateString();

        $response = $this
            ->actingAs($user)
            ->patch(route('expenses.update', $expense), [
                'name' => 'Updated label',
                'amount' => '25.50',
                'category' => 'Transport',
                'date' => $newDate,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', __('Expense updated successfully.'))
            ->assertRedirect(route('records.index'));

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'user_id' => $user->id,
            'name' => 'Updated label',
            'amount' => '25.50',
            'category' => 'Transport',
            'date' => $newDate,
        ]);
    }

    public function test_user_cannot_update_another_users_expense(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $expense = Expense::factory()->for($owner)->create([
            'name' => 'Rent',
            'amount' => '500.00',
            'category' => 'Housing & Utilities',
            'date' => now()->toDateString(),
        ]);

        $response = $this
            ->actingAs($other)
            ->patch(route('expenses.update', $expense), [
                'name' => 'Changed',
                'amount' => '1.00',
                'category' => 'Food',
                'date' => now()->toDateString(),
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'name' => 'Rent',
            'amount' => '500.00',
        ]);
    }

    public function test_update_rejects_invalid_category(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->for($user)->create();

        $response = $this
            ->actingAs($user)
            ->from(route('expenses.edit', $expense))
            ->patch(route('expenses.update', $expense), [
                'name' => $expense->name,
                'amount' => (string) $expense->amount,
                'category' => 'General',
                'date' => $expense->date,
            ]);

        $response
            ->assertSessionHasErrors('category')
            ->assertRedirect(route('expenses.edit', $expense));
    }

    public function test_update_rejects_negative_amount(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->for($user)->create();

        $response = $this
            ->actingAs($user)
            ->from(route('expenses.edit', $expense))
            ->patch(route('expenses.update', $expense), [
                'name' => $expense->name,
                'amount' => '-5.00',
                'category' => $expense->category,
                'date' => $expense->date,
            ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_update_rejects_missing_name(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->for($user)->create();

        $response = $this
            ->actingAs($user)
            ->from(route('expenses.edit', $expense))
            ->patch(route('expenses.update', $expense), [
                'name' => '',
                'amount' => '10.00',
                'category' => 'Food',
                'date' => now()->toDateString(),
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_records_page_shows_edit_link_for_own_expenses(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->for($user)->create([
            'date' => now()->toDateString(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('records.index', [
                'year' => now()->year,
                'month' => now()->month,
            ]));

        $response->assertOk();
        $response->assertSee(route('expenses.edit', $expense), false);
    }
}

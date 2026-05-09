<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringMaterializationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_materializes_monthly_expense_for_viewed_month(): void
    {
        $user = User::factory()->create();
        RecurringExpense::factory()->for($user)->create([
            'name' => 'Rent',
            'amount' => 500,
            'category' => 'Housing & Utilities',
            'day_of_month' => 5,
            'starts_on' => now()->startOfMonth()->toDateString(),
            'ends_on' => null,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', [
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertOk();
        $this->assertSame(
            1,
            Expense::query()->where('user_id', $user->id)->where('name', 'Rent')->count()
        );
        $this->assertStringContainsString('Rent', $response->getContent());
    }

    public function test_materializing_twice_does_not_duplicate_expenses(): void
    {
        $user = User::factory()->create();
        RecurringExpense::factory()->for($user)->create([
            'name' => 'Rent',
            'amount' => 500,
            'category' => 'Housing & Utilities',
            'day_of_month' => 5,
            'starts_on' => now()->startOfMonth()->toDateString(),
        ]);

        $params = ['year' => now()->year, 'month' => now()->month];
        $this->actingAs($user)->get(route('dashboard', $params))->assertOk();
        $this->actingAs($user)->get(route('dashboard', $params))->assertOk();

        $this->assertSame(
            1,
            Expense::query()->where('user_id', $user->id)->where('name', 'Rent')->count()
        );
    }

    public function test_recurring_income_materializes_as_income_row(): void
    {
        $this->travelTo('2026-05-10 12:00:00');

        $user = User::factory()->create();
        $user->recurringIncomes()->create([
            'name' => 'Salary',
            'amount' => 1200,
            'day_of_month' => 25,
            'starts_on' => '2026-01-01',
            'ends_on' => null,
        ]);

        $this->actingAs($user)->get(route('dashboard', ['year' => 2026, 'month' => 5]))->assertOk();

        $this->assertSame(1, Income::query()->where('user_id', $user->id)->where('name', 'Salary')->count());
        $this->assertDatabaseHas('incomes', [
            'user_id' => $user->id,
            'name' => 'Salary',
            'date' => '2026-05-25',
        ]);
    }

    public function test_occurrence_before_starts_on_does_not_materialize_for_that_month(): void
    {
        $this->travelTo('2026-05-20 12:00:00');

        $user = User::factory()->create();
        RecurringExpense::factory()->for($user)->create([
            'name' => 'Late bill',
            'amount' => 40,
            'category' => 'Miscellaneous',
            'day_of_month' => 5,
            'starts_on' => '2026-05-15',
            'ends_on' => null,
        ]);

        $this->actingAs($user)->get(route('dashboard', ['year' => 2026, 'month' => 5]))->assertOk();

        $this->assertSame(0, Expense::query()->where('user_id', $user->id)->where('name', 'Late bill')->count());
    }

    public function test_day_after_month_end_maps_to_last_day_of_month(): void
    {
        $this->travelTo('2026-02-10 12:00:00');

        $user = User::factory()->create();
        RecurringExpense::factory()->for($user)->create([
            'name' => 'End month',
            'amount' => 99,
            'category' => 'Miscellaneous',
            'day_of_month' => 31,
            'starts_on' => '2026-01-01',
            'ends_on' => null,
        ]);

        $this->actingAs($user)->get(route('dashboard', ['year' => 2026, 'month' => 2]))->assertOk();

        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'date' => '2026-02-28',
            'name' => 'End month',
        ]);
    }

    public function test_occurrence_after_ends_on_does_not_materialize(): void
    {
        $this->travelTo('2026-03-01 12:00:00');

        $user = User::factory()->create();
        RecurringExpense::factory()->for($user)->create([
            'name' => 'Short contract',
            'amount' => 50,
            'category' => 'Miscellaneous',
            'day_of_month' => 28,
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-02-20',
        ]);

        $this->actingAs($user)->get(route('dashboard', ['year' => 2026, 'month' => 3]))->assertOk();

        $this->assertSame(0, Expense::query()->where('user_id', $user->id)->where('name', 'Short contract')->count());
    }

    public function test_records_page_only_shows_transactions_for_selected_month(): void
    {
        $this->travelTo('2026-05-15 12:00:00');

        $user = User::factory()->create();
        Expense::factory()->for($user)->create([
            'name' => 'January only',
            'date' => '2026-01-10',
            'category' => 'Food',
        ]);
        Expense::factory()->for($user)->create([
            'name' => 'May item',
            'date' => '2026-05-10',
            'category' => 'Food',
        ]);

        $response = $this->actingAs($user)->get(route('records.index', [
            'year' => 2026,
            'month' => 5,
        ]));

        $response->assertOk();
        $response->assertSeeText('May item');
        $response->assertDontSeeText('January only');
    }

    public function test_recurring_materialize_command_exits_successfully(): void
    {
        User::factory()->create();

        $this->artisan('recurring:materialize')->assertSuccessful();
    }
}

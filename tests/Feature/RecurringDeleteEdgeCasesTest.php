<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringExpense;
use App\Models\User;
use App\Services\RecurringMaterializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringDeleteEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_materializer_does_not_recreate_expense_after_delete_purged_rule(): void
    {
        $user = User::factory()->create();
        $rule = RecurringExpense::factory()->for($user)->create([
            'name' => 'Stream',
            'amount' => 9.99,
            'category' => 'Entertainment & Subscriptions',
            'day_of_month' => 12,
            'starts_on' => '2026-01-12',
        ]);
        $june = Expense::factory()->for($user)->create([
            'recurring_expense_id' => $rule->id,
            'name' => 'Stream',
            'amount' => 9.99,
            'category' => 'Entertainment & Subscriptions',
            'date' => '2026-06-12',
        ]);

        $this->actingAs($user)->from(route('records.index', ['year' => 2026, 'month' => 6]))
            ->delete(route('expenses.destroy', $june))
            ->assertRedirect();

        app(RecurringMaterializer::class)->materializeMonth($user, 2026, 6);

        $this->assertSame(
            0,
            Expense::query()->where('user_id', $user->id)->where('name', 'Stream')->count()
        );
    }

    public function test_materializer_does_not_recreate_income_after_delete_purged_rule(): void
    {
        $user = User::factory()->create();
        $rule = $user->recurringIncomes()->create([
            'name' => 'Payroll',
            'amount' => 800,
            'day_of_month' => 28,
            'starts_on' => '2026-01-28',
            'ends_on' => null,
        ]);
        $row = $user->incomes()->create([
            'recurring_income_id' => $rule->id,
            'name' => 'Payroll',
            'amount' => 800,
            'date' => '2026-06-28',
        ]);

        $this->actingAs($user)->delete(route('income.destroy', $row));

        app(RecurringMaterializer::class)->materializeMonth($user, 2026, 6);

        $this->assertSame(
            0,
            Income::query()->where('user_id', $user->id)->where('name', 'Payroll')->count()
        );
    }

    public function test_deleting_expense_does_not_remove_another_users_same_name_and_day(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $bobRule = RecurringExpense::factory()->for($bob)->create([
            'name' => 'Rent',
            'amount' => 500,
            'category' => 'Housing & Utilities',
            'day_of_month' => 1,
            'starts_on' => '2026-01-01',
        ]);
        $bobExpense = Expense::factory()->for($bob)->create([
            'recurring_expense_id' => $bobRule->id,
            'name' => 'Rent',
            'amount' => 500,
            'category' => 'Housing & Utilities',
            'date' => '2026-06-01',
        ]);

        $aliceExpense = Expense::factory()->for($alice)->create([
            'recurring_expense_id' => null,
            'name' => 'Rent',
            'amount' => 600,
            'category' => 'Housing & Utilities',
            'date' => '2026-06-01',
        ]);

        $this->actingAs($alice)->delete(route('expenses.destroy', $aliceExpense));

        $this->assertDatabaseHas('expenses', ['id' => $bobExpense->id]);
        $this->assertDatabaseHas('recurring_expenses', ['id' => $bobRule->id]);
    }

    public function test_deleting_expense_only_purges_rules_for_that_name_and_billing_day_not_other_day(): void
    {
        $user = User::factory()->create();

        $ruleFirst = RecurringExpense::factory()->for($user)->create([
            'name' => 'Same Label',
            'amount' => 10,
            'category' => 'Food',
            'day_of_month' => 1,
            'starts_on' => '2026-01-01',
        ]);
        $ruleFifteenth = RecurringExpense::factory()->for($user)->create([
            'name' => 'Same Label',
            'amount' => 20,
            'category' => 'Food',
            'day_of_month' => 15,
            'starts_on' => '2026-01-15',
        ]);

        $onFirst = Expense::factory()->for($user)->create([
            'recurring_expense_id' => $ruleFirst->id,
            'name' => 'Same Label',
            'amount' => 10,
            'category' => 'Food',
            'date' => '2026-03-01',
        ]);
        Expense::factory()->for($user)->create([
            'recurring_expense_id' => $ruleFifteenth->id,
            'name' => 'Same Label',
            'amount' => 20,
            'category' => 'Food',
            'date' => '2026-03-15',
        ]);

        $this->actingAs($user)->delete(route('expenses.destroy', $onFirst));

        $this->assertDatabaseMissing('recurring_expenses', ['id' => $ruleFirst->id]);
        $this->assertDatabaseHas('recurring_expenses', ['id' => $ruleFifteenth->id]);
        $this->assertSame(1, Expense::query()->where('user_id', $user->id)->count());
    }

    public function test_deleting_expense_purges_all_matching_rules_when_duplicates_exist(): void
    {
        $user = User::factory()->create();

        $ruleA = RecurringExpense::factory()->for($user)->create([
            'name' => 'DupSub',
            'amount' => 10,
            'category' => 'Miscellaneous',
            'day_of_month' => 20,
            'starts_on' => '2026-01-20',
        ]);
        $ruleB = RecurringExpense::factory()->for($user)->create([
            'name' => 'DupSub',
            'amount' => 11,
            'category' => 'Miscellaneous',
            'day_of_month' => 20,
            'starts_on' => '2026-01-20',
        ]);

        $linked = Expense::factory()->for($user)->create([
            'recurring_expense_id' => $ruleA->id,
            'name' => 'DupSub',
            'amount' => 10,
            'category' => 'Miscellaneous',
            'date' => '2026-04-20',
        ]);
        Expense::factory()->for($user)->create([
            'recurring_expense_id' => $ruleB->id,
            'name' => 'DupSub',
            'amount' => 11,
            'category' => 'Miscellaneous',
            'date' => '2026-05-20',
        ]);

        $this->actingAs($user)->delete(route('expenses.destroy', $linked));

        $this->assertSame(0, RecurringExpense::query()->where('user_id', $user->id)->count());
        $this->assertSame(0, Expense::query()->where('user_id', $user->id)->count());
    }

    public function test_orphan_expense_delete_matches_whitespace_and_case_in_name(): void
    {
        $user = User::factory()->create();
        $feb = Expense::factory()->for($user)->create([
            'recurring_expense_id' => null,
            'name' => '  Sky TV  ',
            'amount' => 99,
            'category' => 'Entertainment & Subscriptions',
            'date' => '2026-02-25',
        ]);
        $apr = Expense::factory()->for($user)->create([
            'recurring_expense_id' => null,
            'name' => 'sky tv',
            'amount' => 99,
            'category' => 'Entertainment & Subscriptions',
            'date' => '2026-04-25',
        ]);

        $this->actingAs($user)->delete(route('expenses.destroy', $feb));

        $this->assertDatabaseMissing('expenses', ['id' => $apr->id]);
        $this->assertSame(0, Expense::query()->where('user_id', $user->id)->count());
    }

    public function test_orphan_expense_delete_removes_rows_with_differing_amount_category_same_schedule(): void
    {
        $user = User::factory()->create();
        $canonical = 'Netflix';
        $day = 8;
        $toDelete = Expense::factory()->for($user)->create([
            'recurring_expense_id' => null,
            'name' => $canonical,
            'amount' => 12,
            'category' => 'Entertainment & Subscriptions',
            'date' => '2026-01-08',
        ]);
        $drift = Expense::factory()->for($user)->create([
            'recurring_expense_id' => null,
            'name' => $canonical,
            'amount' => 15.99,
            'category' => 'Food',
            'date' => '2026-03-08',
        ]);

        $this->actingAs($user)->delete(route('expenses.destroy', $toDelete));

        $this->assertDatabaseMissing('expenses', ['id' => $drift->id]);
    }

    public function test_delete_keeps_expense_with_same_name_on_different_calendar_day(): void
    {
        $user = User::factory()->create();
        $twentyFifthAm = Expense::factory()->for($user)->create([
            'recurring_expense_id' => null,
            'name' => 'Coffee',
            'amount' => 3,
            'category' => 'Food',
            'date' => '2026-02-25',
        ]);
        $twentyFifthPm = Expense::factory()->for($user)->create([
            'recurring_expense_id' => null,
            'name' => 'Coffee',
            'amount' => 3,
            'category' => 'Food',
            'date' => '2026-02-25',
        ]);
        $otherDay = Expense::factory()->for($user)->create([
            'recurring_expense_id' => null,
            'name' => 'Coffee',
            'amount' => 3,
            'category' => 'Food',
            'date' => '2026-02-26',
        ]);

        $this->actingAs($user)->delete(route('expenses.destroy', $twentyFifthAm));

        $this->assertDatabaseMissing('expenses', ['id' => $twentyFifthPm->id]);
        $this->assertDatabaseHas('expenses', ['id' => $otherDay->id]);
    }

    public function test_delete_expense_from_dashboard_redirects_back_to_dashboard(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->for($user)->create([
            'recurring_expense_id' => null,
            'date' => '2026-05-01',
        ]);

        $response = $this->actingAs($user)
            ->from(route('dashboard', ['year' => 2026, 'month' => 5]))
            ->delete(route('expenses.destroy', $expense));

        $response->assertRedirect(route('dashboard', ['year' => 2026, 'month' => 5]));
    }

    public function test_guest_cannot_delete_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->for($user)->create();

        $response = $this->delete(route('expenses.destroy', $expense));

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_user_cannot_delete_another_users_expense(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $expense = Expense::factory()->for($owner)->create();

        $response = $this->actingAs($intruder)->delete(route('expenses.destroy', $expense));

        $response->assertForbidden();
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_income_orphan_delete_matches_different_amounts_same_schedule(): void
    {
        $user = User::factory()->create();
        $a = $user->incomes()->create([
            'recurring_income_id' => null,
            'name' => 'Bonus',
            'amount' => 100,
            'date' => '2026-02-14',
        ]);
        $b = $user->incomes()->create([
            'recurring_income_id' => null,
            'name' => 'Bonus',
            'amount' => 200,
            'date' => '2026-04-14',
        ]);

        $this->actingAs($user)->delete(route('income.destroy', $a));

        $this->assertDatabaseMissing('incomes', ['id' => $b->id]);
    }

    public function test_income_delete_does_not_remove_other_users_same_name_and_day(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $bobRule = $bob->recurringIncomes()->create([
            'name' => 'Wage',
            'amount' => 1000,
            'day_of_month' => 30,
            'starts_on' => '2026-01-30',
            'ends_on' => null,
        ]);
        $bobIncome = $bob->incomes()->create([
            'recurring_income_id' => $bobRule->id,
            'name' => 'Wage',
            'amount' => 1000,
            'date' => '2026-06-30',
        ]);

        $aliceIncome = $alice->incomes()->create([
            'recurring_income_id' => null,
            'name' => 'Wage',
            'amount' => 500,
            'date' => '2026-06-30',
        ]);

        $this->actingAs($alice)->delete(route('income.destroy', $aliceIncome));

        $this->assertDatabaseHas('incomes', ['id' => $bobIncome->id]);
        $this->assertDatabaseHas('recurring_incomes', ['id' => $bobRule->id]);
    }

    public function test_records_page_loads_after_expense_deleted_without_error(): void
    {
        $user = User::factory()->create();
        Expense::factory()->for($user)->create(['date' => '2026-06-01']);

        $this->actingAs($user)->get(route('records.index', ['year' => 2026, 'month' => 6]))->assertOk();
    }

    public function test_dashboard_recent_expenses_includes_edit_and_delete_controls(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->for($user)->create([
            'name' => 'Cafe',
            'date' => '2026-06-05',
        ]);

        $html = $this->actingAs($user)
            ->get(route('dashboard', ['year' => 2026, 'month' => 6]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('dashboard-delete-expense-'.$expense->id, $html);
        $this->assertStringContainsString('/expenses/'.$expense->id.'/edit', $html);
    }
}

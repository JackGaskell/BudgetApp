<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ViewMonth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_income_edit_form(): void
    {
        $user = User::factory()->create();
        $income = $user->incomes()->create([
            'name' => 'Job',
            'amount' => 100,
            'date' => now()->toDateString(),
        ]);

        $response = $this->get(route('income.edit', $income));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_and_update_own_income(): void
    {
        $user = User::factory()->create();
        $income = $user->incomes()->create([
            'name' => 'Old',
            'amount' => 100,
            'date' => '2026-03-01',
        ]);

        $this->actingAs($user)
            ->get(route('income.edit', array_merge(
                ['income' => $income],
                ViewMonth::queryParams(2026, 3)
            )))
            ->assertOk()
            ->assertSeeText('Edit income');

        $response = $this->actingAs($user)->patch(route('income.update', $income), [
            'name' => 'Updated pay',
            'amount' => '150.00',
            'date' => '2026-03-05',
            'return_year' => 2026,
            'return_month' => 3,
        ]);

        $response->assertRedirect(route('records.index', ['year' => 2026, 'month' => 3]));
        $this->assertDatabaseHas('incomes', [
            'id' => $income->id,
            'name' => 'Updated pay',
            'amount' => '150.00',
            'date' => '2026-03-05',
        ]);
    }

    public function test_user_cannot_update_another_users_income(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $income = $owner->incomes()->create([
            'name' => 'Theirs',
            'amount' => 50,
            'date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($other)->patch(route('income.update', $income), [
            'name' => 'Hacked',
            'amount' => '1.00',
            'date' => now()->toDateString(),
        ]);

        $response->assertForbidden();
    }

    public function test_records_page_shows_edit_link_for_income(): void
    {
        $user = User::factory()->create();
        $income = $user->incomes()->create([
            'name' => 'Grant',
            'amount' => 200,
            'date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('records.index', [
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertOk();
        $response->assertSee('/income/'.$income->id.'/edit', false);
        $response->assertSee('year='.now()->year, false);
        $response->assertSee('month='.now()->month, false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_expense_category_dropdown_options(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSeeText('Repeat');
        $response->assertSeeText('Select a category');

        foreach (Expense::CATEGORIES as $category) {
            $response->assertSeeText($category);
        }
    }

    public function test_user_can_create_expense_with_valid_category(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Weekly groceries',
            'amount' => '52.10',
            'category' => 'Food',
            'date' => now()->toDateString(),
        ];

        $response = $this
            ->actingAs($user)
            ->post('/expenses', $payload);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'name' => 'Weekly groceries',
            'amount' => '52.10',
            'category' => 'Food',
            'date' => now()->toDateString(),
        ]);
    }

    public function test_user_cannot_create_expense_with_invalid_category(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/dashboard')
            ->post('/expenses', [
                'name' => 'Random cost',
                'amount' => '14.50',
                'category' => 'General',
                'date' => now()->toDateString(),
            ]);

        $response
            ->assertSessionHasErrors('category')
            ->assertRedirect('/dashboard');

        $this->assertDatabaseMissing('expenses', [
            'user_id' => $user->id,
            'name' => 'Random cost',
            'amount' => '14.50',
            'category' => 'General',
        ]);
    }

    public function test_user_cannot_create_expense_with_negative_amount(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/dashboard')
            ->post('/expenses', [
                'name' => 'Invalid',
                'amount' => '-10.00',
                'category' => 'Food',
                'date' => now()->toDateString(),
            ]);

        $response->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('expenses', [
            'user_id' => $user->id,
            'name' => 'Invalid',
        ]);
    }
}

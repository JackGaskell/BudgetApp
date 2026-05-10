<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringExpense>
 */
class RecurringExpenseFactory extends Factory
{
    protected $model = RecurringExpense::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'amount' => fake()->randomFloat(2, 5, 500),
            'category' => fake()->randomElement(Expense::CATEGORIES),
            'frequency' => RecurringExpense::FREQUENCY_MONTHLY,
            'day_of_month' => fake()->numberBetween(1, 28),
            'day_of_week' => null,
            'starts_on' => now()->startOfMonth()->toDateString(),
            'ends_on' => null,
        ];
    }
}

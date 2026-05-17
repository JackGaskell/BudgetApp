<?php

namespace Database\Factories;

use App\Models\StudentFundingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentFundingPlan>
 */
class StudentFundingPlanFactory extends Factory
{
    protected $model = StudentFundingPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $received = now()->subWeeks(2)->startOfDay();

        return [
            'user_id' => User::factory()->student(),
            'name' => 'Student loan',
            'amount' => 3000,
            'received_on' => $received,
            'next_payment_on' => $received->copy()->addMonths(4),
            'spread_frequency' => StudentFundingPlan::FREQUENCY_MONTHLY,
        ];
    }

    public function weekly(): static
    {
        return $this->state(fn () => [
            'spread_frequency' => StudentFundingPlan::FREQUENCY_WEEKLY,
        ]);
    }
}

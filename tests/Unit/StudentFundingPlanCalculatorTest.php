<?php

namespace Tests\Unit;

use App\Models\StudentFundingPlan;
use App\Models\User;
use App\Services\StudentFundingPlanCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFundingPlanCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private StudentFundingPlanCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(StudentFundingPlanCalculator::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_weekly_spread_divides_loan_by_weeks_in_period(): void
    {
        $user = User::factory()->create();
        $plan = StudentFundingPlan::factory()->for($user)->weekly()->create([
            'amount' => 1200,
            'received_on' => '2026-01-01',
            'next_payment_on' => '2026-01-29',
        ]);

        $snapshot = $this->calculator->snapshot($user, $plan);

        $this->assertSame(StudentFundingPlan::FREQUENCY_WEEKLY, $snapshot['spread_frequency']);
        $this->assertSame(4, $snapshot['spread_periods']);
        $this->assertSame(300.0, $snapshot['spread_amount']);
    }

    public function test_monthly_spread_counts_calendar_months_touched(): void
    {
        $user = User::factory()->create();
        $plan = StudentFundingPlan::factory()->for($user)->create([
            'amount' => 5000,
            'received_on' => '2026-01-15',
            'next_payment_on' => '2026-03-10',
            'spread_frequency' => StudentFundingPlan::FREQUENCY_MONTHLY,
        ]);

        $snapshot = $this->calculator->snapshot($user, $plan);

        $this->assertSame(StudentFundingPlan::FREQUENCY_MONTHLY, $snapshot['spread_frequency']);
        $this->assertSame(3, $snapshot['spread_periods']);
        $this->assertEqualsWithDelta(1666.67, $snapshot['spread_amount'], 0.01);
    }

    public function test_income_and_expenses_outside_period_are_excluded(): void
    {
        Carbon::setTestNow('2026-03-01');

        $user = User::factory()->create();
        $plan = StudentFundingPlan::factory()->for($user)->create([
            'received_on' => '2026-02-01',
            'next_payment_on' => '2026-04-01',
        ]);

        $user->incomes()->create(['name' => 'Before', 'amount' => 999, 'date' => '2026-01-31']);
        $user->incomes()->create(['name' => 'Inside', 'amount' => 100, 'date' => '2026-02-15']);
        $user->incomes()->create(['name' => 'After period end', 'amount' => 888, 'date' => '2026-04-02']);
        $user->expenses()->create([
            'name' => 'Inside',
            'amount' => 50,
            'date' => '2026-02-20',
            'category' => 'Food',
        ]);
        $user->expenses()->create([
            'name' => 'Future scheduled',
            'amount' => 200,
            'date' => '2026-03-20',
            'category' => 'Food',
        ]);

        $snapshot = $this->calculator->snapshot($user, $plan);

        $this->assertSame(100.0, $snapshot['income_so_far']);
        $this->assertSame(50.0, $snapshot['expenses_so_far']);
        $this->assertSame(50.0, $snapshot['net_so_far']);
        $this->assertSame(100.0, $snapshot['income_full_period']);
        $this->assertSame(250.0, $snapshot['expenses_full_period']);
    }

    public function test_on_loan_pace_when_spending_matches_elapsed_share(): void
    {
        Carbon::setTestNow('2026-02-15');

        $user = User::factory()->create();
        $plan = StudentFundingPlan::factory()->for($user)->create([
            'amount' => 3000,
            'received_on' => '2026-01-01',
            'next_payment_on' => '2026-05-01',
        ]);

        $user->expenses()->create([
            'name' => 'On pace',
            'amount' => 1125,
            'date' => '2026-02-10',
            'category' => 'Food',
        ]);

        $snapshot = $this->calculator->snapshot($user, $plan);

        $this->assertTrue($snapshot['on_loan_pace']);
        $this->assertEqualsWithDelta(0.0, $snapshot['over_loan_pace_by'], 0.02);
    }

    public function test_off_loan_pace_when_spending_exceeds_elapsed_share(): void
    {
        Carbon::setTestNow('2026-02-15');

        $user = User::factory()->create();
        $plan = StudentFundingPlan::factory()->for($user)->create([
            'amount' => 3000,
            'received_on' => '2026-01-01',
            'next_payment_on' => '2026-05-01',
        ]);

        $user->expenses()->create([
            'name' => 'Over pace',
            'amount' => 2000,
            'date' => '2026-02-10',
            'category' => 'Food',
        ]);

        $snapshot = $this->calculator->snapshot($user, $plan);

        $this->assertFalse($snapshot['on_loan_pace']);
        $this->assertGreaterThan(0, $snapshot['over_loan_pace_by']);
    }

    public function test_days_remaining_is_zero_on_term_end_date(): void
    {
        Carbon::setTestNow('2026-05-01');

        $user = User::factory()->create();
        $plan = StudentFundingPlan::factory()->for($user)->create([
            'received_on' => '2026-01-01',
            'next_payment_on' => '2026-05-01',
        ]);

        $snapshot = $this->calculator->snapshot($user, $plan);

        $this->assertSame(0, $snapshot['days_remaining']);
        $this->assertEqualsWithDelta(120, $snapshot['days_elapsed'], 0.001);
    }

    public function test_as_of_after_term_end_caps_period_totals_at_end_date(): void
    {
        Carbon::setTestNow('2026-06-15');

        $user = User::factory()->create();
        $plan = StudentFundingPlan::factory()->for($user)->create([
            'received_on' => '2026-01-01',
            'next_payment_on' => '2026-05-01',
        ]);

        $user->incomes()->create(['name' => 'In period', 'amount' => 3000, 'date' => '2026-01-01']);
        $user->incomes()->create(['name' => 'After term', 'amount' => 500, 'date' => '2026-06-01']);
        $user->expenses()->create([
            'name' => 'In period',
            'amount' => 100,
            'date' => '2026-03-01',
            'category' => 'Food',
        ]);
        $user->expenses()->create([
            'name' => 'After term',
            'amount' => 999,
            'date' => '2026-06-10',
            'category' => 'Food',
        ]);

        $snapshot = $this->calculator->snapshot($user, $plan);

        $this->assertSame(3000.0, $snapshot['income_so_far']);
        $this->assertSame(100.0, $snapshot['expenses_so_far']);
    }

    public function test_loan_remaining_simple_is_loan_minus_expenses_so_far(): void
    {
        Carbon::setTestNow('2026-02-01');

        $user = User::factory()->create();
        $plan = StudentFundingPlan::factory()->for($user)->create([
            'amount' => 3000,
            'received_on' => '2026-01-01',
            'next_payment_on' => '2026-05-01',
        ]);

        $user->expenses()->create([
            'name' => 'Spend',
            'amount' => 800,
            'date' => '2026-01-15',
            'category' => 'Food',
        ]);

        $snapshot = $this->calculator->snapshot($user, $plan);

        $this->assertSame(2200.0, $snapshot['loan_remaining_simple']);
    }
}
